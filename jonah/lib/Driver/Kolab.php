<?php
/**
 * Jonah storage implementation for the Kolab backend.
 *
 * The driver requires some SQL storage as well though. The table structure can
 * be created using Horde's db_migrate script.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Ben Klang <ben@alkaloid.net>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @author  Ian Roth <iron_hat@hotmail.com>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Jonah
 */
class Jonah_Driver_Kolab extends Jonah_Driver
{
    /**
     * The Kolab_Storage backend.
     *
     * @var Horde_Kolab_Storage
     */
    private $_kolab;

    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    public function __construct($params = array())
    {
        if (empty($params['storage']) || empty($params['db'])) {
            throw new InvalidArgumentException('Missing required storage/db handler.');
        }
        $this->_kolab = $params['storage'];
        unset($params['storage']);
        $this->_db = $params['db'];
        unset($params['db']);
        parent::__construct($params);
    }

    /**
     * Return the Kolab data handler for the specified feed.
     *
     * @param string $feed The feed name.
     *
     * @return Horde_Kolab_Storage_Date The data handler.
     */
    private function _getDataForFeed($feed)
    {
        return $this->_kolab->getData(
            $GLOBALS['jonah_shares']->getShare($feed)->get('folder'),
            'note'
        );
    }

    /**
     * Saves a channel to the backend.
     *
     * @param array $info  The channel to add.
     *                     Must contain a combination of the following
     *                     entries:
     * <pre>
     * 'channel_id'       If empty a new channel is being added, otherwise one
     *                    is being edited.
     * 'channel_slug'     The channel slug.
     * 'channel_name'     The headline.
     * 'channel_desc'     A description of this channel.
     * 'channel_type'     Whether internal or external.
     * 'channel_interval' If external then interval at which to refresh.
     * 'channel_link'     The link to the source.
     * 'channel_url'      The url from where to fetch the story list.
     * 'channel_image'    A channel image.
     * </pre>
     *
     * @return integer The channel ID.
     * @throws Jonah_Exception
     */
    public function saveChannel(&$info)
    {
        $values = array(Horde_String::convertCharset($info['channel_slug'], 'UTF-8', $this->_params['charset']),
                        Horde_String::convertCharset($info['channel_name'], 'UTF-8', $this->_params['charset']),
                        (int)$info['channel_type'],
                        isset($info['channel_desc']) ? $info['channel_desc'] : null,
                        isset($info['channel_interval']) ? (int)$info['channel_interval'] : null,
                        isset($info['channel_url']) ? $info['channel_url'] : null,
                        isset($info['channel_link']) ? $info['channel_link'] : null,
                        isset($info['channel_page_link']) ? $info['channel_page_link'] : null,
                        isset($info['channel_story_url']) ? $info['channel_story_url'] : null,
                        isset($info['channel_img']) ? $info['channel_img'] : null);
        if (empty($info['channel_id'])) {
            $sql = 'INSERT INTO jonah_channels' .
                   ' (channel_slug, channel_name, channel_type, channel_desc, channel_interval, channel_url, channel_link, channel_page_link, channel_story_url, channel_img)' .
                   ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

            try {
                $info['channel_id'] = $this->_db->insert($sql, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Jonah_Exception($e);
            }
        } else {
            $values[] = (int)$info['channel_id'];
            $sql = 'UPDATE jonah_channels' .
                   ' SET channel_slug = ?, channel_name = ?, channel_type = ?, channel_desc = ?, channel_interval = ?, channel_url = ?, channel_link = ?, channel_page_link = ?, channel_story_url = ?, channel_img = ?' .
                   ' WHERE channel_id = ?';

            try {
                $results = $this->_db->update($sql, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Jonah_Exception($e);
            }
        }

        return $info['channel_id'];
    }

    /**
     * Update the channel's timestamp
     *
     * @param integer $channel_id  The channel id.
     * @param integer $timestamp   The new timestamp.
     *
     * @return boolean
     * @throws Jonah_Exception
     */
    protected function _timestampChannel($channel_id, $timestamp)
    {
        //@todo: Timestamp the share here (or generate the last timestamp in another way).
        return;
        $sql = sprintf('UPDATE jonah_channels SET channel_updated = %s WHERE channel_id = %s',
                       (int)$timestamp,
                       (int)$channel_id);

        try {
            $result = $this->_db->update($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }

        return $result;
    }

    /**
     * Increment the story's read count.
     *
     * @param integer $story_id  The story_id to increment.
     * @throws Jonah_Exception
     */
    protected function _readStory($story_id)
    {
        $sql = 'UPDATE jonah_stories SET story_read = story_read + 1 WHERE story_id = ' . (int)$story_id;

        try {
            $result = $this->_db->update($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }

        return $result;
    }

    /**
     * Save a story to storage.
     *
     * @param array &$info  The story info array.
     * @throws Jonah_Exception
     * @return Integer      Id of story
     */
    protected function _saveStory(&$info)
    {
        if (empty($info['id'])) {
            $data = $this->_getDataForFeed($info['channel_id']);
            $uid = $data->generateUid();
            $object = array(
                'uid' => $uid,
                'summary' => isset($info['title']) ? $info['title'] : null,
                'body' => isset($info['body']) ? $info['body'] : null,
                'categories' => isset($info['tags']) ? $info['tags'] : null,
                'link-attachment' => isset($info['url']) ? $info['url'] : null,
            );
            $data->create($object);
        }

        if (empty($info['read'])) {
            $info['read'] = 0;
        }

        $values = array($info['channel_id'],
                        Horde_String::convertCharset($info['author'], 'UTF-8', $this->_params['charset']),
                        $uid,
                        Horde_String::convertCharset($info['description'], 'UTF-8', $this->_params['charset']),
                        $info['body_type'],
                        isset($info['published']) ? (int)$info['published'] : null,
                        time(),
                        (int)$info['read']);
        if (empty($info['id'])) {
            $channel = Jonah::getFeed($info['channel_id']);
            $permalink = $this->getStoryLink($channel, $info);
            $values[] = $permalink;
            $sql = 'INSERT INTO jonah_stories (channel_id, story_author, story_title, story_desc, story_body_type, story_published, story_updated, story_read, story_permalink) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';

            try {
                $info['id'] = $this->_db->insert($sql, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Jonah_Exception($e);
            }
        } else {
            $values[] = (int)$info['id'];
            $sql = 'UPDATE jonah_stories SET channel_id = ?, story_author = ?, story_title = ?, story_desc = ?, story_body_type = ?, story_body = ?, story_url = ?, story_published = ?, story_updated = ?, story_read = ? WHERE story_id = ?';

            try {
                $result = $this->_db->update($sql, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Jonah_Exception($e);
            }
        }

        $this->_timestampChannel($info['channel_id'], time());

        return $info['id'];
    }

    /**
     * Converts the text fields of a story from the backend charset to the
     * output charset.
     *
     * @param array $story  A story hash.
     *
     * @return array  The converted hash.
     */
    protected function _convertFromBackend($story)
    {
        $story['title'] = Horde_String::convertCharset($story['title'], $this->_params['charset'], 'UTF-8');
        $story['description'] = Horde_String::convertCharset($story['description'], $this->_params['charset'], 'UTF-8');
        if (isset($story['body'])) {
            $story['body'] = Horde_String::convertCharset($story['body'], $this->_params['charset'], 'UTF-8');
        }

        return $story;
    }

    /**
     * Returns the total number of stories in the specified channel.
     *
     * @param integer $channel_id  The Channel Id
     *
     * @return integer  The count
     */
    public function getStoryCount($channel_id)
    {
        $sql = 'SELECT count(*) FROM jonah_stories WHERE channel_id = ?';
        try {
            $result = $this->_db->SelectOne($sql, $channel_id);
        } catch (Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }

        return (int)$result;
    }

    /**
     * Returns a list of stories from the storage backend filtered by
     * arbitrary criteria.
     * NOTE: $criteria['channel_id'] MUST be set for this method to work.
     *
     * @param array $criteria
     *
     * @return array
     *
     * @see Jonah_Driver#getStories
     */
    protected function _getStories($criteria, $order = Jonah::ORDER_PUBLISHED)
    {
        $sql = 'SELECT DISTINCT(stories.story_id) AS id, ' .
           'stories.channel_id, ' .
           'stories.story_author AS author, ' .
           'stories.story_title AS title, ' .
           'stories.story_desc AS description, ' .
           'stories.story_body_type AS body_type, ' .
           'stories.story_body AS body, ' .
           'stories.story_url AS url, ' .
           'stories.story_permalink AS permalink, ' .
           'stories.story_published AS published, ' .
           'stories.story_updated AS updated, ' .
           'stories.story_read AS readcount ' .
           'FROM jonah_stories AS stories ' .
           'WHERE stories.channel_id=?';

        $values = array($criteria['channel_id']);

        // Apply date filtering
        if (isset($criteria['updated-min'])) {
            $sql .= ' AND story_updated >= ?';
            $values[] = $criteria['updated-min']->timestamp();
        }
        if (isset($criteria['updated-max'])) {
            $sql .= ' AND story_updated <= ?';
            $values[] = $criteria['updated-max']->timestamp();
        }
        if (isset($criteria['published-min'])) {
            $sql .= ' AND story_published >= ?';
            $values[] = $criteria['published-min']->timestamp();
        }
        if (isset($criteria['published-max'])) {
            $sql .= ' AND story_published <= ?';
            $values[] = $criteria['published-max']->timestamp();
        }
        if (isset($criteria['published'])) {
            $sql .= ' AND story_published IS NOT NULL';
        }

        // Apply tag filtering
        if (isset($criteria['tags'])) {
            $sql .= ' AND (';
            $multiple = false;
            foreach ($criteria['tags'] as $tag) {
                if (!empty($criteria['tagIDs'][$tag])) {
                    if ($multiple) {
                        $sql .= ' OR ';
                    }
                    $sql .= 'tags.tag_id = ?';
                    $values[] = $criteria['tagIDs'][$tag];
                    $multiple = true;
                }
            }
            $sql .= ')';
        }

        if (isset($criteria['alltags'])) {
            $sql .= ' AND (';
            $multiple = false;
            foreach ($criteria['alltags'] as $tag) {
                if ($multiple) {
                    $sql .= ' AND ';
                }
                $sql .= 'tags.tag_id = ?';
                $values[] = $criteria['tagIDs'][$tag];
                $multiple = true;
            }
            $sql .= ')';
        }

        // Filter by story author
        if (isset($criteria['author'])) {
            $sql .= ' AND stories.story_author = ?';
            $values[] = $criteria['author'];
        }

        // Filter stories by keyword
        if (isset($criteria['keywords'])) {
            foreach ($criteria['keywords'] as $keyword) {
                $sql .= ' AND stories.story_body LIKE ?';
                $values[] = '%' . $keyword . '%';
            }
        }
        if (isset($criteria['notkeywords'])) {
            foreach ($criteria['notkeywords'] as $keyword) {
                $sql .= ' AND stories.story_body NOT LIKE ?';
                $values[] = '%' . $keyword . '%';
            }
        }

        switch ($order) {
        case Jonah::ORDER_PUBLISHED:
            $sql .= ' ORDER BY published DESC';
            break;
        case Jonah::ORDER_READ:
            $sql .= ' ORDER BY readcount DESC';
            break;
        case Jonah::ORDER_COMMENTS:
            //@TODO
            break;
        }
        $limit = 0;
        $start = 0;
        $limitOffset = array();
        if (isset($criteria['limit'])) {
            $limit = $criteria['limit'];
        }
        if (isset($criteria['startnumber']) && isset($criteria['endnumber'])) {
            $limit = min($criteria['endnumber'] - $criteria['startnumber'], $criteria['limit']);
            $start = $criteria['startnumber'];
        }
        if ($limit) {
            $limitOffset['limit'] = $limit;
        }
        if ($start) {
            $limitOffset['offset'] = $start;
        }
        $sql = $this->_db->addLimitOffset($sql, $limitOffset);

        try {
            $results = $this->_db->selectAll($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }
        foreach ($results as &$row) {
            $row['tags'] = $this->readTags($row['id']);
            $kolab = $this->_getKolabStory(
                $criteria['channel_id'], $row['title']
            );
            $row['title'] = $kolab['title'];
            $row['body'] = $kolab['body'];
            $row['url'] = $kolab['url'];
        }
        return $results;
    }

    /**
     * Obtain a channel id from a slug
     *
     * @param string $slug  The slug to search for.
     *
     * @return integer  The channel id.
     */
    protected function _getIdBySlug($slug)
    {
        // @TODO
        throw new Jonah_Exception('Not implemented yet.');
    }

    /**
     * Retrieve a story from storage.
     *
     * @param integer $story_id  They story id.
     * @param boolean $read      Increment the read counter?
     *
     * @return The story array.
     * @throws Horde_Exception_NotFound
     * @throws Jonah_Exception
     *
     */
    protected function _getStory($story_id, $read = false)
    {
        $sql = 'SELECT stories.story_id as id, ' .
           'stories.channel_id, ' .
           'stories.story_author AS author, ' .
           'stories.story_title AS title, ' .
           'stories.story_desc AS description, ' .
           'stories.story_body_type AS body_type, ' .
           'stories.story_body AS body, ' .
           'stories.story_url AS url, ' .
           'stories.story_permalink AS permalink, ' .
           'stories.story_published AS published, ' .
           'stories.story_updated AS updated, ' .
           'stories.story_read AS readcount ' .
           'FROM jonah_stories AS stories WHERE stories.story_id=?';

        $values = array((int)$story_id);

        try {
            $result = $this->_db->selectOne($sql, $values, DB_FETCHMODE_ASSOC);
        } catch (Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }
        if (empty($result)) {
            throw new Horde_Exception_NotFound(sprintf(_("Story id \"%s\" not found."), $story_id));
        }

        $kolab = $this->_getKolabStory(
            $result['channel_id'], $result['title']
        );
        $result['title'] = $kolab['title'];
        $result['body'] = $kolab['body'];
        $result['url'] = $kolab['url'];

        if ($read) {
            $this->_readStory($story_id);
        }

        return $result;
    }

    private function _getKolabStory($channel, $guid)
    {
        $data = $this->_getDataForFeed($channel);
        $object = $data->getObject($guid);
        return array(
            'title' => $object['summary'],
            'body' => $object['body'],
            'url' => $object['link-attachment'],
            'tags' => $object['categories'],
            'updated' => $object['last-modification-date']
        );
    }

    /**
     * Adds a missing permalink to a story.
     *
     * @param array $story  A story hash.
     * @throws Jonah_Exception
     */
    protected function _addPermalink(&$story)
    {
        $channel = $this->getChannel($story['channel_id']);
        $sql = 'UPDATE jonah_stories SET story_permalink = ? WHERE story_id = ?';
        $values = array($this->getStoryLink($channel, $story), $story['id']);
        try {
            $result = $this->_db->update($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }
        $story['permalink'] = $values[0];
    }

    /**
     * Gets the latest released story from a given internal channel
     *
     * @param int $channel_id  The channel id.
     *
     * @return int  The story id.
     * @throws Jonah_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getLatestStoryId($channel_id)
    {
        $sql = 'SELECT story_id FROM jonah_stories' .
               ' WHERE channel_id = ? AND story_published <= ?' .
               ' ORDER BY story_updated DESC';
        $values = array($channel_id, time());

        try {
            $result = $this->_db->selectOne($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Jonah_Exception($e);
        }

        if (empty($result)) {
            return Horde_Exception_NotFound(sprintf(_("Channel \"%s\" not found."), $channel_id));
        }

        return $result['story_id'];
    }

    /**
     */
    public function deleteStory($channel_id, $story_id)
    {
        $sql = 'DELETE FROM jonah_stories' .
               ' WHERE channel_id = ? AND story_id = ?';
        $values = array($channel_id, (int)$story_id);

        try {
            $result = $this->_db->delete($sql, $values);
        } catch (Horde_Db_Exception $e) {}

        return true;
    }
}
