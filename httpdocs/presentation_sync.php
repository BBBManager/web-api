<?php
ini_set('display_errors', 'on');
ini_set('error_reporting', E_ALL);
ini_set('date.timezone', 'America/Sao_Paulo');
set_time_limit(0);

try {
    defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../private/application'));
    defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));
    set_include_path(implode(PATH_SEPARATOR, array(realpath(APPLICATION_PATH . '/../library'))));
    require_once 'Zend/Application.php';

    $applicationIniFilePath = APPLICATION_PATH . '/configs/application.ini';

    $application = new Zend_Application(APPLICATION_ENV, $applicationIniFilePath);
    $application->bootstrap();

    $bbbRootPathString = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array('var', 'bigbluebutton'));
    $bbbRawRecordingPathString = implode(DIRECTORY_SEPARATOR, array($bbbRootPathString, 'recording', 'raw'));
    $bbbProcessingRecordingsPathString = implode(DIRECTORY_SEPARATOR, array($bbbRootPathString, 'recording', 'process', 'presentation'));
    $bbbProcessedRecordingsPathString = implode(DIRECTORY_SEPARATOR, array($bbbRootPathString, 'recording', 'status', 'processed'));

    $bbbRawRecordingPath = realpath($bbbRawRecordingPathString);
    $bbbProcessingRecordingsPath = realpath($bbbProcessingRecordingsPathString);

    if ($bbbRawRecordingPath === false) {
        throw new Exception('Invalid raw recording path ' . $bbbRawRecordingPathString);
    }

    if ($bbbProcessingRecordingsPath === false) {
        throw new Exception('Invalid processing recordings path ' . $bbbProcessingRecordingsPathString);
    }

    $dbAdapter = Zend_Db_Table::getDefaultAdapter();
    $recordingsSelect = $dbAdapter->select()->from('record')->columns(array('bbb_id'));
    $recordingsDbCollection = $dbAdapter->fetchAll($recordingsSelect);

    $dbRecordings = array();

    if (($recordingsDbCollection != null) && (count($recordingsDbCollection) > 0)) {
        foreach ($recordingsDbCollection as $dbRecording) {
            $dbRecordings[$dbRecording['bbb_id']] = $dbRecording;
        }
    }

    $processingRecordingsIteratorFlags = FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS;
    $processingRecordingIterator = new RecursiveDirectoryIterator($bbbProcessingRecordingsPath, $processingRecordingsIteratorFlags);

    $syncInsertedRecordings = array();
    $syncUpdatedRecordings = array();

    foreach ($processingRecordingIterator as $recording) {
        $recordingId = $recording->getFilename();

        if (isset($dbRecordings[$recordingId]) && $dbRecordings[$recordingId]['sync_done'] == '1') {
            echo 'Skipping recording ' . $recordingId . ' already synced' . PHP_EOL;
            continue;
        }

        $rawMetadataFilePath = implode(DIRECTORY_SEPARATOR, array($bbbRawRecordingPathString, $recordingId, 'events.xml'));
        if (!file_exists($rawMetadataFilePath)) {
            echo 'Skipping recording ' . $recordingId . ' raw metadata file not found - ' . $rawMetadataFilePath;
            continue;
        }

        $xmlString = file_get_contents($rawMetadataFilePath);
        $dd = new DomDocument();
        $rawXmlData = @$dd->loadXML($xmlString);

        if ($rawXmlData === false) {
            echo 'Skipping recording ' . $recordingId . ' raw metadata file invalid - ' . $rawMetadataFilePath;
            continue;
        }

        $xp = new DOMXpath($dd);

        $eventsInMeeting = $dd->getElementsByTagName('event');
        $firstInMeeting = $eventsInMeeting->item(0);
        $lastInMeeting = $xp->query('event[@eventname="EndAndKickAllEvent"]')->item(0);

        $javaToPhpStartTime = $firstInMeeting->getAttribute('timestamp') / 1000;
        $javaToPhpEndTime = $lastInMeeting->getAttribute('timestamp') / 1000;

        $recordingMetadataContent = $dd->getElementsByTagName('metadata')->item(0)->getAttribute('xml');
        $metadataNode = simplexml_load_string($recordingMetadataContent);
        $meetingId = $metadataNode->meetingID;
        $meetingName = $metadataNode->meetingName;

        $recordingReady = file_exists(implode(DIRECTORY_SEPARATOR, array($bbbProcessedRecordingsPathString, $recordingId . '-presentation.done')));
        /*
          +-----------------+--------------+------+-----+-------------------+----------------+
          | Field           | Type         | Null | Key | Default           | Extra          |
          +-----------------+--------------+------+-----+-------------------+----------------+
          | record_id       | int(11)      | NO   | PRI | NULL              | auto_increment |
          | meeting_room_id | int(11)      | NO   | MUL | NULL              |                |
          | name            | varchar(100) | YES  |     | NULL              |                |
          | date_start      | timestamp    | YES  |     | NULL              |                |
          | date_end        | timestamp    | YES  |     | NULL              |                |
          | public          | tinyint(1)   | YES  |     | NULL              |                |
          | create_date     | timestamp    | NO   |     | CURRENT_TIMESTAMP |                |
          | last_update     | timestamp    | YES  |     | NULL              |                |
          | bbb_id          | varchar(80)  | YES  |     | NULL              |                |
          +-----------------+--------------+------+-----+-------------------+----------------+
         */
        $recordingModel = new BBBManager_Model_Record();

        try {
            $meetingRoomModel = new BBBManager_Model_MeetingRoom();
            $meetingRoomRecord = $meetingRoomModel->fetchRow($meetingRoomModel->select()->where('meeting_room_id = ?', $meetingId));
            if ($meetingRoomRecord == null) {
                /* Recording that references a deleted meeting room, skip this recording */
                echo 'Skipping recording ' . $recordingId . ' - references a deleted meeting room ' . $meetingId . PHP_EOL;
                continue;
            }

            $recordingModel->getAdapter()->beginTransaction();

            $recordingRow = $recordingModel->fetchRow($recordingModel->select()->where('bbb_id = ?', $recordingId)->where('meeting_room_id = ?', $meetingId));

            $inserting = false;
            $updating = false;

            if ($recordingRow != null && $recordingReady == true) {
                $updateData = array(
                    'sync_done' => '1'
                );

                $where = array();
                $where[] = $recordingModel->getAdapter()->quoteInto('bbb_id = ?', $recordingId);
                $where[] = $recordingModel->getAdapter()->quoteInto('meeting_room_id = ?', $meetingId);

                $updating = true;
                echo 'Will update meeetingId = ' . $meetingId . PHP_EOL;
                $recordingModel->update($updateData, $where);
            } elseif ($recordingRow == null) {
                echo 'Will insert recording ' . $recordingId . ', referencing meeting ' . $meetingId . PHP_EOL;

                $inserting = true;

                $insertingData = array(
                    'bbb_id' => $recordingId,
                    'meeting_room_id' => $meetingId,
                    'date_start' => date('Y-m-d H:i:s', $javaToPhpStartTime),
                    'date_end' => date('Y-m-d H:i:s', $javaToPhpEndTime),
                    'name' => $meetingName,
                    'playback_url' => IMDT_Util_Config::getInstance()->get('web_base_url') . '/playback/presentation/playback.html?meetingId=' . $recordingId
                );

                if ($recordingReady) {
                    $insertingData['sync_done'] = 1;
                }

                $recordingModel->insert($insertingData);
            }

            $recordingModel->getAdapter()->commit();

            if ($updating) {
                $syncUpdatedRecordings[] = $recordingId;
            }

            if ($inserting) {
                $syncInsertedRecordings[] = $recordingId;
            }
        } catch (Exception $ex) {
            echo 'Erro :' . $ex->getMessage() . PHP_EOL;
            $recordingModel->getAdapter()->rollback();
        }
    }

    echo "\n";
    echo date('Y-m-d H:i') . ' - ' . count($syncInsertedRecordings) . ' recordings inserted, ' . count($syncUpdatedRecordings) . ' updated';
    echo "\n";
} catch (Exception $e) {
    echo "\n\nERRO: \n\n" . $e->getMessage() . "\n";
    die;
}
