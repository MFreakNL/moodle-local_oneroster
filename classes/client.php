<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_oneroster;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/filelib.php');

/**
 *
 * This is a class containing constants and static functions for general use around the plugin
 *
 * @package     local_oneroster
 * @copyright   2019 Michael Gardener <mgardener@cissq.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client {
    /**
     *
     */
    const RECORD_LIMIT = 100;

    /**
     * @var
     */
    private $token;
    /**
     * @var
     */
    private $config;

    /**
     * cURL synchronous requests handle.
     *
     * @var resource|null
     */
    private $handle;

    /**
     * oneroster constructor.
     */
    public function __construct() {
        $this->config = get_config('local_oneroster');
        $this->token = $this->get_access_token();
    }

    /**
     * Release resources if still active.
     */
    public function __destruct() {
        if (is_resource($this->handle)) {
            curl_close($this->handle);
        }
    }

    /**
     * @return bool
     * @throws \dml_exception
     */
    private function get_access_token() {
        $authorization = base64_encode("{$this->config->clientid}:{$this->config->secretkey}");
        $header = array("Authorization:Basic {$authorization}");
        $content = "grant_type=client_credentials";

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->config->baseurl.'/datahub/oauth/token',
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $content
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        if ($response === false) {
            return false;
        }

        return json_decode($response)->access_token;
    }

    /**
     * @param $request
     * @return mixed
     */
    public function send_request($request, $offset = 0) {
        if (is_resource($this->handle)) {
            curl_reset($this->handle);
        } else {
            $this->handle = curl_init();
        }

        $params = "?limit=".self::RECORD_LIMIT."&offset=".$offset;

        curl_setopt_array($this->handle, [
            CURLOPT_URL => $this->config->baseurl.'/datahub/services/ims/oneroster/v1p1/'.$request.$params,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$this->token}"],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true
        ]);

        $response = curl_exec($this->handle);

        $errno = curl_errno($this->handle);
        switch ($errno) {
            case CURLE_OK:
                break;
            case CURLE_COULDNT_RESOLVE_PROXY:
            case CURLE_COULDNT_RESOLVE_HOST:
            case CURLE_COULDNT_CONNECT:
            case CURLE_OPERATION_TIMEOUTED:
            case CURLE_SSL_CONNECT_ERROR:
                throw new Exception\NetworkException(curl_error($this->handle));
            default:
                throw new Exception\RequestException(curl_error($this->handle));
        }
        return json_decode($response, true);
    }
}
