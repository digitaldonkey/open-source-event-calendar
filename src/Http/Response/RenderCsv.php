<?php

namespace Osec\Http\Response;

/**
 * Render the request as csv
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Render_Strategy_Csv
 * @author     Time.ly Network Inc.
 */
class RenderCsv extends RenderStrategyAbstract
{
    /*
     * @see RenderStrategyAbstract::render()
     */
    public function render(array $params)
    {
        $this->cleanOutputBuffers();

        $now      = gmdate('D, d M Y H:i:s');
        $filename = $params['filename'];

        header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');
        header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
        header('Last-Modified: ' . $now . ' GMT');

        // force download
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');

        // disposition / encoding on response body
        header(
            'Content-Disposition: attachment;filename="' . addcslashes(
                (string)$filename,
                '"'
            ) . '"'
        );
        header('Content-Transfer-Encoding: binary');

        $columns = $params['columns'];
        for ($i = 0, $iMax = count($columns); $i < $iMax; $i++) {
            if ($i > 0) {
                echo(',');
            }
            echo($columns[$i]);
        }
        echo("\n");

        $data = $params['data'];
        for ($i = 0, $iMax = count($data); $i < $iMax; $i++) {
            $row = $data[$i];
            foreach ($row as $j => $jValue) {
                if ($j > 0) {
                    echo(',');
                }
                echo($jValue);
            }
            echo("\n");
        }
        ResponseHelper::stop();
    }
}
