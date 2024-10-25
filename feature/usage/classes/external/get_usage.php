<?php

namespace forumngfeature_usage\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class get_usage extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters Parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'type' => new external_value(PARAM_TEXT, ''),
            'param' => new external_value(PARAM_TEXT, '')
        ]);
    }

    /**
     * Returned data from execute function.
     *
     * @return external_single_structure Return data
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'responseText' => new external_value(PARAM_RAW, 'desription')
        ]);
    }

    /**
     * executes function
     *
     * @param string $type
     * @param string $param
     * @return array Result data
     */
    public static function execute(string $type, string $param): array {
        ['type' => $type, 'param' => $param] = self::validate_parameters(self::execute_parameters(), ['type' => $type, 'param' => $param]);

        $test = $_GET;
        global $CFG, $DB;
        //require_once(dirname(__FILE__) . '/../../../../config.php');
        require_once($CFG->dirroot . '/mod/forumng/feature/usage/locallib.php');
        require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');

//        require_sesskey();
        $func = "forumngfeature_usage_show_$type";

        if (function_exists($func)) {
            parse_str($param, $param);
            //return json_encode(array('content' => $func($param)));
            return  ['responseText' => json_encode(array('content' => $func($param)))];
        }
    }
}