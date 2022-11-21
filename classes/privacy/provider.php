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

namespace enrol_trainingcredits\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

class provider implements
        // This plugin stores user data.
        \core_privacy\local\metadata\provider,

        // This plugin contains user's enrolments.
        \core_privacy\local\request\plugin\provider,

        // This plugin is capable of determining which users have data within it.
        \core_privacy\local\request\core_userlist_provider {


    /**
     * Provides meta data that is stored about a user with mod_assign
     *
     * @param  collection $collection A collection of meta data items to be added to.
     * @return  collection Returns the collection of metadata.
     */
    public static function get_metadata(collection $collection) : collection {
        $trainingcredits = [
                'userid' => 'privacy:metadata:userid',
                'coursecredits' => 'privacy:metadata:coursecredits',
        ];

        $collection->add_database_table('enrol_trainingcredits', $trainingcredits, 'privacy:metadata:trainingcredits');
    }

    /**
     * Any training credits are stored as user context info.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        return context_user::instance($userid);
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_system) {
            return;
        }

        if (!$context instanceof \context_course) {
            return;
        }

        // Context is a user context.
        $userlist->add_user($context->instanceid);
    }

    /**
     * Delete all user data which matches the specified deletion_criteria.
     *
     * @param context $context A user context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (empty($context)) {
            return;
        }
        if ($context->contextlevel == CONTEXT_USER) {
            $DB->delete_records('enrol_trainingcredits', ['userid' => $context->instanceid]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $contexts = $contextlist->get_contexts();
        foreach ($contexts as $ctx) {
            if ($ctx->contextlevel == CONTEXT_USER) {
                $DB->delete_records('enrol_trainingcredits', ['userid' => $ctx->instanceid]);
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist   $userlist   The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userids = $userlist->get_userids();
        foreach ($userids as $uid) {
            $DB->delete_records('enrol_trainingcredits', ['userid' => $uid]);
        }
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $ctx) {
            if ($ctx->contextlevel == CONTEXT_USER) {
                $data = new StdClass;
                $data->usercredits = $DB->get_field('enrol_trainingcredits', 'coursecredits', ['userid' => $user->id]);

                $instance->export_data(null, $data);
            }
        }
    }
}