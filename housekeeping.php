<?php

    /**
    * housekeeping.php
    *
    * Removes various annoying hidden and/or useless files leftover from
    * different people doing different stuff on the filesystem using
    * various OSes.
    *
    * @author   zytzagoo <zytzagoo at gmail dot com>
    * @url      http://zytzagoo.net/blog/?p=43
    * @version  0.2
    *
    * @license  The MIT License http://www.opensource.org/licenses/mit-license.php
    *
    * @usage    rdir_cleanup('.'); // just a 'test-run' in the current working dir
    * @usage    rdir_cleanup('.', true); // nuke everything from the cwd and further below
    * @notice   Exclusions are possible. See the comments for rdir_cleanup()
    *
    * Copyright (c) 2008 zytzagoo.
    *
    * Permission is hereby granted, free of charge, to any person obtaining a copy
    * of this software and associated documentation files (the "Software"), to deal
    * in the Software without restriction, including without limitation the rights
    * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    * copies of the Software, and to permit persons to whom the Software is
    * furnished to do so, subject to the following conditions:
    *
    * The above copyright notice and this permission notice shall be included in
    * all copies or substantial portions of the Software.
    *
    * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    * THE SOFTWARE.
    */

    /**
    * changelog:
    * - Suggesteion by suk: http://zytzagoo.net/blog/?p=43#comment-1751
    *   -> added different new line printing depending on the running environment
    */

    /**
    * A global array of filenames which are to be found
    * note: MacOS hidden files (those starting with '._') are
    * always searched for, because I find them specially annoying.
    */
    $annoying_filenames = array(
        '.DS_Store', // mac specific
        '.localized', // mac specific
        'Thumbs.db', // windows specific
		'.gitignore',
		'.git',
    );

    // shorthand for either \n or <br/>\n depending on the env the script is running in
    if (PHP_SAPI === 'cli') {
       define('NEW_LINE', PHP_EOL);
    } else {
       define('NEW_LINE', '<br/>' . PHP_EOL);
    }

    /**
    * Recursively scan directories for presence of MacOS hidden and
    * other annoying files. If specified, the function will delete them.
    *
    * @global   array   $annoying_files An array of filenames which are
    *                                   considered "annoying" (to say the least)
    * @param    string  $dir            The starting directory
    * @param    bool    $do_delete      Delete the annoying files or just print
    *                                   them out (if they're found). Default false.
    * @param    array   $exclude        An array of files/folders to exclude from
    *                                   the scan, defaults to '.' and '..'
    */
    function rdir_cleanup($dir, $do_delete = false, $exclude = array('.', '..')) {

        global $annoying_filenames;
        $d = opendir($dir);

        while ($f = readdir($d)) {

            // skip the files/dirs that are to be excluded (exact string matching)
            if (in_array($f, $exclude, true)) {
                continue;
            }

            // check if it's a MacOS hidden file
            $mac_hidden = strpos($f, '._');
            if ($mac_hidden !== false && $mac_hidden === 0) {
                /**
                * Assume it's a mac hidden file only if it starts with
                * a dot and an underscore, other files might have that
                * string somewhere else in the filename and we don't want to
                * nuke those.
                */
                echo NEW_LINE . $dir . DIRECTORY_SEPARATOR . $f . ' appears to be a mac hidden file.';
                if ($do_delete === true) {
                    if (unlink($dir . DIRECTORY_SEPARATOR . $f) === true) {
                        echo ' deleted.';
                    }
                }
            }

            // check if it is an annoying filename
            if (in_array($f, $annoying_filenames, true)) {
                echo NEW_LINE . $dir . DIRECTORY_SEPARATOR . $f . ' matched an annoying filename.';
                if ($do_delete === true) {
                    if (unlink($dir . DIRECTORY_SEPARATOR . $f) === true) {
                        echo ' deleted.';
                    }
                }
            }

            // recurse further if needed
            if (is_dir($dir . DIRECTORY_SEPARATOR . $f)) {
                rdir_cleanup($dir . DIRECTORY_SEPARATOR . $f, $do_delete, $exclude);
                continue;
            }

        }

        closedir($d);

    }
