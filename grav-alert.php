<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Uri;
/**
 * Class GravAlertPlugin
 * @package Grav\Plugin
 */
class GravAlertPlugin extends Plugin
{
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        // Enable the main event we are interested in
        $this->enable([
            'onFatalException' => ['onFatalException', 0]
        ]);
    }

    public function onFatalException()
    {
        $slack_hook = trim($this->grav['config']->get('plugins.grav-alert.slack_hook', ''));
        $slack_delay = intval(trim($this->grav['config']->get('plugins.grav-alert.slack_delay', '')));
        if (file_exists('logs/grav_alert_time.log'))
        {
            $last_message_time = intval(file_get_contents('logs/grav_alert_time.log'));
        }
        else
        {
            file_put_contents('logs/grav_alert_time.log', 1);
            $last_message_time = 1;
        }

        $time_dif = time() - $last_message_time;
        if (empty($slack_hook)) return;
        $page_url = $this->grav['uri']->url;
        if ($time_dif > $slack_delay)
        {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $slack_hook);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "{'text':'Homepage crashed - ". $page_url ."'}");

            $headers = array();
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
            }
            curl_close($ch);
            file_put_contents('logs/grav_alert_time.log', time());
        }
    }
}
