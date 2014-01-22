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

/**
 * Local library file for forumng.  These are non-standard functions that are used
 * only by the forumng export feature.
 *
 * @package    mod
 * @subpackage forumng
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 **/

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/forumng/mod_forumng.php');
require_once($CFG->dirroot . '/mod/forumng/mod_forumng_cron.php');
require_once($CFG->libdir . '/portfolio/caller.php');

abstract class forumng_portfolio_caller_base extends portfolio_module_caller_base {
    protected $modcontext;
    protected $files = array();

    // Attachments: array of arrays of stored_file, keyed on versionid
    protected $attachments = array();

    protected function load_base_data($forumngid) {
        global $DB, $COURSE;

        $this->forumng = $DB->get_record(
                'forumng', array('id' => $forumngid), '*', MUST_EXIST);

        if (!empty($COURSE->id) && $COURSE->id == $this->forumng->course) {
            $course = $COURSE;
        } else {
            $course = $DB->get_record(
                    'course', array('id' => $this->forumng->course), '*', MUST_EXIST);
        }

        $modinfo = get_fast_modinfo($course);
        $instances = $modinfo->get_instances_of('forumng');
        if (!array_key_exists($this->forumng->id, $instances)) {
            throw new portfolio_caller_exception('error_export', 'forumng');
        }
        $this->cm = $instances[$this->forumng->id];
        $this->modcontext = context_module::instance($this->cm->id);
    }

    /**
     * Adds all the files from the given pageversions.
     * @param array $pageversions
     */
    protected function add_files() {

        $fs = get_file_storage();

         /*
         * decoding the array from letters to numbers, see export.php
         */
        if ($this->posts !== '') {
            $selected = $this->decode_string_to_array();
        } else {
            $discussion = mod_forumng_discussion::get_from_id($this->discussionid, $this->cloneid);
            $rootpost = $discussion->get_root_post();
            $allposts = array();
            $rootpost->build_linear_children($allposts);
            $selected = array();
            $forum = $discussion->get_forum();
            foreach ($allposts as $post) {
                if (!$post->get_deleted() || has_capability('mod/forumng:viewallposts', $forum->get_context())) {
                    $selected[] = $post->get_id();
                }
            }
        }

        foreach ($selected as $post) {
            $attach = $fs->get_area_files($this->modcontext->id, 'mod_forumng', 'attachment',
                        $post, "sortorder, itemid, filepath, filename", false);
            $this->attachments[$post] = $attach;
            $embed  = $fs->get_area_files($this->modcontext->id, 'mod_forumng', 'message',
                    $post, "sortorder, itemid, filepath, filename", false);
            $this->files = array_merge($this->files, $attach, $embed);
        }

        $this->set_file_and_format_data($this->files);

        if (empty($this->multifiles) && !empty($this->singlefile)) {
            $this->multifiles = array($this->singlefile); // copy_files workaround
        }
        // If there are files, change to rich/plain
        if (!empty($this->multifiles)) {
            $this->add_format(PORTFOLIO_FORMAT_RICHHTML);
        } else {
            $this->add_format(PORTFOLIO_FORMAT_PLAINHTML);
        }
    }

    /**
     * @param array $files Array of file items to copy
     * @return void
     */
    protected function copy_files($files) {
        if (empty($files)) {
            return;
        }
        foreach ($files as $f) {
            $this->get('exporter')->copy_existing_file($f);
        }
    }

    public function get_navigation() {
        global $CFG;

        $discussion = mod_forumng_discussion::get_from_id($this->discussionid, $this->cloneid);
        $navlinks[] = array(
            'name' => $discussion->get_subject(),
            'link' => $CFG->wwwroot . '/mod/forumng/discuss.php?d='. $discussion->get_id(),
            'type' => 'title'
        );
        return array($navlinks, $this->cm);
    }

    public function expected_time() {
        return $this->expected_time_file();
    }

    public function check_permissions() {
        $context = context_module::instance($this->cm->id);
        return (has_capability('mod/forumng:view', $context));
    }

    public static function display_name() {
        return get_string('modulename', 'forumng');
    }

    public function heading_summary() {
        $discussion = mod_forumng_discussion::get_from_id($this->discussionid, $this->cloneid);
        return get_string('exportingcontentfrom', 'portfolio', strtolower(get_string('discussion', 'forumng')).
                ': '.$discussion->get_subject());
    }

    public static function base_supported_formats() {
        return array(PORTFOLIO_FORMAT_RICHHTML, PORTFOLIO_FORMAT_PLAINHTML);
    }

    /**
     * @param string $name Name to be used in filename
     * @return string Safe version of name (replaces unknown characters with _)
     */
    protected function make_filename_safe($name) {
        return preg_replace('~[^A-Za-z0-9 _!,.-]~u', '_', $name);
    }

    protected function decode_string_to_array() {
        $numbers = array('a' => '0', 'b' => '1', 'c' => '2', 'd' => '3', 'e' => '4',
                'f' => '5', 'g' => '6', 'h' => '7', 'i' => '8', 'j' => '9');
        $selarray = '';
        $array = str_split($this->posts);
        foreach ($array as $char) {
            $selarray .= ($char == 'x')? $char:$numbers[$char];
        }

        return explode('x', $selarray);
    }
}

/**
 * Portfolio class for exporting the contents of an entire discussion.
 */
class forumng_all_portfolio_caller extends forumng_portfolio_caller_base {
    protected $forumngid;
    protected $cloneid;
    protected $posts;
    protected $discussionid;
    protected $filenames = array();
    protected $content = '';

    public static function expected_callbackargs() {
        return array(
            'forumngid' => true,
            'cloneid' => true,
            'posts' => true,
            'discussionid' => true);
    }

    public function load_data() {
        global $DB, $COURSE;

        // Load base data
        $this->load_base_data($this->forumngid);

        // Get all files used in the discussion or selected posts.
        $this->add_files();
    }

    public function get_return_url() {
        $url = new moodle_url('/mod/forumng/discuss.php',
                array('d' => $this->discussionid, 'clone' => $this->cloneid));
        return $url->out(false);
    }

    public function prepare_package() {
        global $CFG;
        $plugin = $this->get('exporter')->get('instance')->get('plugin');
        /*
         * decoding the array from letters to numbers, see export.php
         */
        if ($this->posts !== '') {
            $selected = $this->decode_string_to_array();
        } else {
            $selected = false;
        }

        // Set up the start of the XHTML file.
        $allhtml = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" ' .
                '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' .
                html_writer::start_tag('html', array('xmlns' => 'http://www.w3.org/1999/xhtml'));
        $allhtml .= html_writer::tag('head',
                html_writer::empty_tag('meta',
                    array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=utf-8')) .
                html_writer::tag('title', get_string('export', 'forumngfeature_export')));
        $allhtml .= html_writer::start_tag('body') . "\n";
        $poststext = '';
        $postshtml = '';

        // we need a discussion object
        $discussion = mod_forumng_discussion::get_from_id($this->discussionid, $this->cloneid);
        $discussion->build_selected_posts_email($selected, $poststext, $postshtml,
                array(mod_forumng_post::OPTION_EXPORT => true));
        $allhtml .= $postshtml;
        if ($plugin == 'rtf' && isset($this->discussionids)) {
            // Support discussions e.g. all into one file.
            if ($this->discussionid != $this->discussionids[0]) {
                // Make this text just body if not first discussion.
                $allhtml = $postshtml;
            }
            if ($this->discussionid == $this->discussionids[count($this->discussionids) - 1]) {
                // Finish the file if last discussion (might be first and last if only 1).
                $allhtml .= html_writer::end_tag('body') . html_writer::end_tag('html');
            }
        } else {
            // Finish the file.
            $allhtml .= html_writer::end_tag('body') . html_writer::end_tag('html');
        }

        // Remove embedded img and attachment paths.
        $portfolioformat = $this->get('exporter')->get('format');
        foreach ($this->files as $file) {
            $filename = $file->get_filename();
            $urlencfilename = rawurlencode($filename);
            $portfoliofiledir = $portfolioformat->get_file_directory();

            if ($plugin == 'download') {
                // non-encoded embedded image filenames.
                $pattern = '/src="[^"]*'.preg_quote($filename).'\"/';
                $replace = 'src="'.$portfoliofiledir.$filename.'"';
                $allhtml = preg_replace($pattern, $replace, $allhtml);

                // urlencoded embedded image filenames.
                $pattern = '/src="[^"]*'.preg_quote($urlencfilename).'\"/';
                $replace = 'src="'.$portfoliofiledir.$urlencfilename.'"';
                $allhtml = preg_replace($pattern, $replace, $allhtml);

                // non-encoded attached filenames.
                $pattern = '/href="[^"]*'.preg_quote($filename).'\"/';
                $replace = 'href="'.$portfoliofiledir.$filename.'"';
                $allhtml = preg_replace($pattern, $replace, $allhtml);

                // urlencoded attached filenames.
                $pattern = '/href="[^"]*'.preg_quote($urlencfilename).'\"/';
                $replace = 'href="'.$portfoliofiledir.$urlencfilename.'"';
                $allhtml = preg_replace($pattern, $replace, $allhtml);
            }

            if ($plugin == 'rtf') {
                $pattern = '/src="[^"]*'.$filename.'\"/';
                $replace = 'src="'.$portfoliofiledir.$filename.'"';
                $allhtml = preg_replace($pattern, $replace, $allhtml);

                $pattern = '/src=\"http:\/\/.*?'.preg_quote($filename).'.*?\"/';
                $replace = 'src="'.$portfoliofiledir.$filename.'"';
                $allhtml = preg_replace($pattern, $replace, $allhtml);

                $pattern = '/src=\"http:\/\/.*?'.preg_quote($urlencfilename).'.*?\"/';
                $replace = 'src="'.$portfoliofiledir.$filename.'"';
                $allhtml = preg_replace($pattern, $replace, $allhtml);
            }
        }

        $content = $allhtml;
        if ($plugin == 'rtf' && isset($this->discussionids)) {
            // Different functionality if multiple discussions.
            if ($this->discussionid != $this->discussionids[count($this->discussionids) - 1]) {
                $this->content .= $content;
                return;
            } else {
                $content = $this->content . $content;
                $forum = mod_forumng::get_from_cmid($this->cm->id, $this->cloneid);
                $name = $this->make_filename_safe($forum->get_name()) . '.html';
            }
        } else {
            $name = $this->make_filename_safe($discussion->get_subject(true));
            if (in_array($name, $this->filenames)) {
                // Make unique filename.
                for ($a = 1; $a < 100; $a++) {
                    if (!in_array("$name$a", $this->filenames)) {
                        $name = "$name$a";
                        break;
                    }
                }
            }
            $this->filenames[] = $name;
            $name .= '.html';
        }

        $manifest = ($this->exporter->get('format') instanceof PORTFOLIO_FORMAT_RICH);

        $this->copy_files($this->multifiles);
        $this->get('exporter')->write_new_file($content, $name, $manifest);
    }

    public function get_sha1() {
        $filesha = '';
        if (!empty($this->multifiles)) {
            $filesha = $this->get_sha1_file();
        }
        $bigstring = $filesha;

        return sha1($bigstring);
    }
}

/**
 * Portfolio class for exporting the contents of multiple discussions.
 */
class forumng_discussions_portfolio_caller extends forumng_all_portfolio_caller {
    protected $discussionids;

    public static function expected_callbackargs() {
        return array(
                'forumngid' => true,
                'cloneid' => true,
                'discussionids' => true);
    }

    public function heading_summary() {
        $forum = mod_forumng::get_from_cmid($this->cm->id, $this->cloneid);
        return get_string('exportingcontentfrom', 'portfolio', strtolower(get_string('forum', 'forumng')).
                ': ' . $forum->get_name());
    }

    public function load_data() {
        global $DB, $COURSE;

        // Load base data
        $this->load_base_data($this->forumngid);

        // Get all files used in the discussions.
        if ($this->discussionids == '') {
            // Fallback in case nothing sent - get every discussion in forum!
            $this->discussionids = array();
            $groupid = mod_forumng::get_activity_group($this->cm, true);
            if ($groupid == -1) {
                $groupid = null;
            }
            $discussions = $DB->get_records('forumng_discussions',
                    array('forumngid' => $this->cm->instance, 'groupid' => $groupid), '', 'id, postid');
            foreach ($discussions as $discussionrec) {
                if (!$discussionrec->postid) {
                    continue;
                }
                $discussion = mod_forumng_discussion::get_from_id($discussionrec->id, $this->cloneid);
                if ($discussion->can_view()) {
                    $this->discussionids[] = $discussion->get_id();
                }
            }
        } else {
            $this->posts = $this->discussionids;// Set so decode works.
            $this->discussionids = $this->decode_string_to_array();
            $this->posts = '';
        }
        if (empty($this->discussionids)) {
            throw new moodle_exception('exportallnodisc', 'forumngfeature_export', $this->get_return_url());
        }
        foreach ($this->discussionids as $discussionid) {
            $this->discussionid = $discussionid;
            $this->add_files();
        }
    }

    public function get_return_url() {
       $url = new moodle_url('/mod/forumng/view.php',
                array('id' => $this->cm->id, 'clone' => $this->cloneid));
       return $url->out(false);
    }

    public function prepare_package() {
        $baseurl = $this->get_return_url();
        $this->content = '';
        foreach ($this->discussionids as $discussionid) {
            $this->discussionid = $discussionid;
            parent::prepare_package();
        }
    }

    public function get_sha1() {
        $filesha = '';
        if (!empty($this->multifiles)) {
            $filesha = $this->get_sha1_file();
        }
        $bigstring = $filesha;

        return sha1($bigstring);
    }

    public static function base_supported_formats() {
        return array(PORTFOLIO_FORMAT_RICHHTML);
    }

    public function get_navigation() {
        return array(array(), $this->cm);
    }
}
