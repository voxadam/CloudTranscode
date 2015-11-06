<?php

/**
 * This class validate input assets and get metadata about them
 * It makes sure the input files to be transcoded exists and is valid.
 * Based on the input file type we lunch the proper transcoder
 */

require_once __DIR__.'/BasicActivity.php';

use SA\CpeSdk;

class ValidateAssetActivity extends BasicActivity
{
    /** @var \finfo */
    private $finfo;

    public function __construct($params, $debug, $cpeLogger = null)
    {
        parent::__construct($params, $debug, $cpeLogger);
        $this->finfo = new \finfo(FILEINFO_MIME_TYPE);
    }

    // Perform the activity
    public function do_activity($task)
    {
        $this->cpeLogger->log_out(
            "INFO",
            basename(__FILE__),
            "Preparing Asset validation ...",
            $this->activityLogKey
        );

        // Call parent do_activity:
        // It download the input file we will process.
        parent::do_activity($task);

        // Fetch first 1 KiB of file
        $this->send_heartbeat($task);
        $obj = $this->s3->getObject([
            'Bucket' => $this->input->{'input_asset'}->{'bucket'},
            'Key' => $this->input->{'input_asset'}->{'file'},
            'Range' => '0-1024'
        ]);
        $this->send_heartbeat($task);

        // Determine file type
        $mime = $this->finfo->buffer($obj['Body']);
        $type = substr($mine, 0, strpos($mime, '/'));

        // Load the right transcoder base on input_type
        // Get asset detailed info
        switch ($type)
        {
        case 'audio':
        case 'video':
        case 'image':
        default:
            require_once __DIR__.'/transcoders/VideoTranscoder.php';

            // Initiate transcoder obj
            $videoTranscoder = new VideoTranscoder($this, $task);
            // Get input video information
            $assetInfo = $videoTranscoder->get_asset_info($this->pathToInputFile);
            $assetInfo['mime'] = $mime;
            $assetInfo['type'] = $type;

            return $assetInfo;
        }
    }
}
