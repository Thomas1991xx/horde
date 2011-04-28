<?php
/**
 * Horde_Imap_Client:: provides an abstracted API interface to various IMAP
 * backends (RFC 3501).
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Imap_Client
 */
class Horde_Imap_Client
{
    /* Constants for openMailbox() */
    const OPEN_READONLY = 1;
    const OPEN_READWRITE = 2;
    const OPEN_AUTO = 3;

    /* Constants for listMailboxes() */
    const MBOX_SUBSCRIBED = 1;
    const MBOX_SUBSCRIBED_EXISTS = 2;
    const MBOX_UNSUBSCRIBED = 3;
    const MBOX_ALL = 4;

    /* Constants for status() */
    const STATUS_MESSAGES = 1;
    const STATUS_RECENT = 2;
    const STATUS_UIDNEXT = 4;
    const STATUS_UIDVALIDITY = 8;
    const STATUS_UNSEEN = 16;
    const STATUS_ALL = 32;
    const STATUS_FIRSTUNSEEN = 64;
    const STATUS_FLAGS = 128;
    const STATUS_PERMFLAGS = 256;
    const STATUS_HIGHESTMODSEQ = 512;
    const STATUS_LASTMODSEQ = 1024;
    const STATUS_LASTMODSEQUIDS = 2048;
    const STATUS_UIDNOTSTICKY = 4096;

    /* Constants for search() */
    const SORT_ARRIVAL = 1;
    const SORT_CC = 2;
    const SORT_DATE = 3;
    const SORT_FROM = 4;
    const SORT_REVERSE = 5;
    const SORT_SIZE = 6;
    const SORT_SUBJECT = 7;
    const SORT_TO = 8;
    /* SORT_THREAD provided for completeness - it is not a valid sort criteria
     * for search() (use thread() instead). */
    const SORT_THREAD = 9;
    /* Sort criteria defined in RFC 5957 */
    const SORT_DISPLAYFROM = 10;
    const SORT_DISPLAYTO = 11;
    /* SORT_SEQUENCE does a simple numerical sort on the returned
     * UIDs/sequence numbers. */
    const SORT_SEQUENCE = 12;
    /* Fuzzy sort criteria defined in RFC 6203 */
    const SORT_RELEVANCY = 13;

    /* Search results constants */
    const SEARCH_RESULTS_COUNT = 1;
    const SEARCH_RESULTS_MATCH = 2;
    const SEARCH_RESULTS_MAX = 3;
    const SEARCH_RESULTS_MIN = 4;
    const SEARCH_RESULTS_SAVE = 5;
    /* Fuzzy sort criteria defined in RFC 6203 */
    const SEARCH_RESULTS_RELEVANCY = 6;

    /* DEPRECATED: Use SEARCH_RESULTS_* instead. */
    const SORT_RESULTS_COUNT = 1;
    const SORT_RESULTS_MATCH = 2;
    const SORT_RESULTS_MAX = 3;
    const SORT_RESULTS_MIN = 4;
    const SORT_RESULTS_SAVE = 5;

    /* Constants for thread() */
    const THREAD_ORDEREDSUBJECT = 1;
    const THREAD_REFERENCES = 2;
    const THREAD_REFS = 3;

    /* Fetch criteria constants. */
    const FETCH_STRUCTURE = 1;
    const FETCH_FULLMSG = 2;
    const FETCH_HEADERTEXT = 3;
    const FETCH_BODYTEXT = 4;
    const FETCH_MIMEHEADER = 5;
    const FETCH_BODYPART = 6;
    const FETCH_BODYPARTSIZE = 7;
    const FETCH_HEADERS = 8;
    const FETCH_ENVELOPE = 9;
    const FETCH_FLAGS = 10;
    const FETCH_IMAPDATE = 11;
    const FETCH_SIZE = 12;
    const FETCH_UID = 13;
    const FETCH_SEQ = 14;
    const FETCH_MODSEQ = 15;

    /* IMAP data types (RFC 3501 [4]) */
    const DATA_ASTRING = 1;
    const DATA_ATOM = 2;
    const DATA_DATETIME = 3;
    const DATA_LISTMAILBOX = 4;
    const DATA_MAILBOX = 5;
    const DATA_NSTRING = 6;
    const DATA_NUMBER = 7;
    const DATA_STRING = 8;

    /* Namespace constants. */
    const NS_PERSONAL = 1;
    const NS_OTHER = 2;
    const NS_SHARED = 3;

    /* ACL constants (RFC 4314 [2.1]). */
    const ACL_LOOKUP = 'l';
    const ACL_READ = 'r';
    const ACL_SEEN = 's';
    const ACL_WRITE = 'w';
    const ACL_INSERT = 'i';
    const ACL_POST = 'p';
    const ACL_CREATEMBOX = 'k';
    const ACL_DELETEMBOX = 'x';
    const ACL_DELETEMSGS = 't';
    const ACL_EXPUNGE = 'e';
    const ACL_ADMINISTER = 'a';
    // Deprecated constants (RFC 2086 [3]; RFC 4314 [2.1.1])
    const ACL_CREATE = 'c';
    const ACL_DELETE = 'd';

    /* System flags. */
    // RFC 3501 [2.3.2]
    const FLAG_ANSWERED = '\\answered';
    const FLAG_DELETED = '\\deleted';
    const FLAG_DRAFT = '\\draft';
    const FLAG_FLAGGED = '\\flagged';
    const FLAG_RECENT = '\\recent';
    const FLAG_SEEN = '\\seen';
    // RFC 3503 [3.3]
    const FLAG_MDNSENT = '$mdnsent';
    // RFC 5550 [2.8]
    const FLAG_FORWARDED = '$forwarded';

    /* Special-use mailbox attributes (RFC 6154 [2]). */
    const SPECIALUSE_ALL = '\\All';
    const SPECIALUSE_ARCHIVE = '\\Archive';
    const SPECIALUSE_DRAFTS = '\\Drafts';
    const SPECIALUSE_FLAGGED = '\\Flagged';
    const SPECIALUSE_JUNK = '\\Junk';
    const SPECIALUSE_SENT = '\\Sent';
    const SPECIALUSE_TRASH = '\\Trash';

    /**
     * Attempts to return a concrete Horde_Imap_Client instance based on
     * $driver.
     *
     * @param string $driver  The type of concrete subclass to return.
     * @param array $params   Configuration parameters:
     * <pre>
     * Required Parameters:
     * --------------------
     * password - (string) The IMAP user password.
     * username - (string) The IMAP username.
     *
     * Optional Parameters:
     * --------------------
     * cache - (array) If set, caches data from fetch(), search(), and
     *         thread() calls. Requires the horde/Cache package to be
     *         installed. The array can contain the following keys (see
     *         Horde_Imap_Client_Cache:: for default values):
     *   cacheob - [REQUIRED] (Horde_Cache) The cache object to use.
     *   fetch_ignore - (array) A list of mailboxes to ignore when storing
     *                  fetch data.
     *   fields - (array) The fetch criteria to cache. If not defined, all
     *            cacheable data is cached. The following is a list of
     *            criteria that can be cached:
     *              + Horde_Imap_Client::FETCH_ENVELOPE
     *              + Horde_Imap_Client::FETCH_FLAGS
     *                Only if server supports CONDSTORE extension
     *              + Horde_Imap_Client::FETCH_HEADERS
     *                Only for queries that specifically request caching
     *              + Horde_Imap_Client::FETCH_IMAPDATE
     *              + Horde_Imap_Client::FETCH_SIZE
     *              + Horde_Imap_Client::FETCH_STRUCTURE
     *   lifetime - (integer) The lifetime of the cache data (in seconds).
     *   slicesize - (integer) The slicesize to use.
     * capability_ignore - (array) A list of IMAP capabilites to ignore, even
     *                     if they are supported on the server.
     *                     DEFAULT: No supported capabilities are ignored
     * comparator - (string) The search comparator to use instead of the
     *              default IMAP server comparator. See
     *              Horde_Imap_Client_Base::setComparator() for the format.
     *              DEFAULT: Use the server default
     * debug - (string) If set, will output debug information to the stream
     *         identified. The value can be any PHP supported wrapper that can
     *         be opened via fopen().
     *         DEFAULT: No debug output
     * encryptKey - (array) A callback to a function that returns the key
     *              used to encrypt the password. This function MUST be
     *              static.
     *              DEFAULT: No encryption
     * hostspec - (string) The hostname or IP address of the server.
     *            DEFAULT: 'localhost'
     * id - (array) Send ID information to the IMAP server (only if server
     *      supports the ID extension). An array with the keys being the
     *      fields to send and the values being the associated values. See RFC
     *      2971 [3.3] for a list of defined field values.
     *      DEFAULT: No info sent to server
     * lang - (array) A list of languages (in priority order) to be used to
     *        display human readable messages.
     *        DEFAULT: Messages output in IMAP server default language
     * log - (array) A callback to a function that receives a single
     *       parameter: a Horde_Imap_Client_Exception object. This callback
     *       function MUST be static.
     *       DEFAULT: No logging
     * port - (integer) The server port to which we will connect.
     *         DEFAULT: 143 (imap or imap w/TLS) or 993 (imaps)
     * secure - (string) Use SSL or TLS to connect.
     *          VALUES: false, 'ssl', 'tls'.
     *          DEFAULT: No encryption
     * statuscache - (boolean) Cache STATUS responses?
     *               DEFAULT: False
     * timeout - (integer)  Connection timeout, in seconds.
     *           DEFAULT: 30 seconds
     * </pre>
     *
     * @return Horde_Imap_Client_Base  The newly created instance.
     * @throws Horde_Imap_Client_Exception
     */
    static public function factory($driver, $params = array())
    {
        $class = __CLASS__ . '_' . strtr(ucfirst(basename($driver)), '-', '_');
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Imap_Client_Exception('Driver ' . $driver . ' not found', Horde_Imap_Client_Exception::DRIVER_NOT_FOUND);
    }

}
