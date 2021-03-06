<?php

namespace CrossKnowledge\SubConverterBundle\Providers;

/**
 * Web VTT subtitles class
 */
class WebVttSubtitles extends Subtitles
{
    /**
     * Return true if the provided file is in the current format
     * @param string $filename
     * @return boolean
     */
    public function checkFormat($filename)
    {
        $contents = str_replace("\r", "", self::removeBom(file_get_contents($filename)));

        return preg_match("/^WEBVTT\n+/mU", $contents, $matches);
    }

    /**
     * Import the provided file
     * @param string $filename
     * @return Subtitles
     * @throws \Exception
     */
    public function import($filename)
    {
        if (!$this->checkFormat($filename)) {
            throw new \Exception("Invalid WebVTT file: ".basename($filename));
        }

        $contents = str_replace("\r", "", self::forceUtf8(file_get_contents($filename)));

        preg_match_all(
            "/([0-9]+)[[:space:]]*\n" .
            "([0-9]{2}):([0-9]{2}):([0-9]{2})\.([0-9]{3}) --> ([0-9]{2}):([0-9]{2}):([0-9]{2})\.([0-9]{3})[[:space:]]*\n" .
            "(.+\n\n|.+\Z)/smU",
            $contents,
            $matches,
            PREG_SET_ORDER
        );

        if (empty($matches)) {
            throw new \Exception("Invalid WebVTT file: ".basename($filename));
        }

        $this->subtitles = array();

        foreach ($matches as $aMatch) {
            $timeFrom = 3600 * $aMatch[2] + 60 * $aMatch[3] + $aMatch[4] + ('0.'.$aMatch[5]);
            $timeTo = 3600 * $aMatch[6] + 60 * $aMatch[7] + $aMatch[8] + ('0.'.$aMatch[9]);
            $text = trim($aMatch[10], " \t\r\n");

            $this->subtitles[] = array(
                'from' => $timeFrom,
                'to' => $timeTo,
                'text' => self::textToHtml($text),
            );
        }

        return $this;
    }

    /**
     * Export the subtitles in the current format
     * @param boolean $bom Add UTF-8 BOM
     * @return string
     */
    public function export($bom = false)
    {
        $webvtt = "WEBVTT\n\n";

        $i = 1;
        foreach ($this->subtitles as $row) {
            $webvtt .= "$i\n";
            $webvtt .= self::formatSeconds($row['from'], '.', 3).' --> '.self::formatSeconds($row['to'], '.', 3)."\n";
            $webvtt .= self::htmlToText($row['text'])."\n\n";

            $i++;
        }

        if ($bom) {
            $webvtt = self::addUtf8Bom($webvtt);
        }

        return $webvtt;
    }

    /**
     * Return file extension for the current format
     * @return string
     */
    public function getFileExt()
    {
        return 'vtt';
    }
}

?>
