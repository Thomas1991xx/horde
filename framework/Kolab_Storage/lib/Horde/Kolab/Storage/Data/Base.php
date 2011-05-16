<?php
/**
 * The basic handler for data objects in a Kolab storage folder.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * The basic handler for data objects in a Kolab storage folder.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Data_Base
implements Horde_Kolab_Storage_Data, Horde_Kolab_Storage_Data_Query
{
    /**
     * The link to the parent folder object.
     *
     * @var Horde_Kolab_Folder
     */
    private $_folder;

    /**
     * The driver for accessing the Kolab storage system.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_driver;

    /**
     * The factory for generating additional resources.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    private $_factory;

    /**
     * The folder type.
     *
     * @var string
     */
    private $_type;

    /**
     * The version of the data.
     *
     * @var int
     */
    private $_version;

    /**
     * The list of registered queries.
     *
     * @var array
     */
    private $_queries = array();

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Folder  $folder  The folder to retrieve the
     *                                             data from.
     * @param Horde_Kolab_Storage_Driver  $driver  The primary connection driver.
     * @param Horde_Kolab_Storage_Factory $factory The factory.
     * @param string                      $type     The type of data we want to
     *                                              access in the folder.
     * @param int                         $version Format version of the object
     *                                             data.
     */
    public function __construct(
        Horde_Kolab_Storage_Folder $folder,
        Horde_Kolab_Storage_Driver $driver,
        Horde_Kolab_Storage_Factory $factory,
        $type = null,
        $version = 1
    ) {
        $this->_folder  = $folder;
        $this->_driver  = $driver;
        $this->_factory = $factory;
        $this->_type    = $type;
        $this->_version = $version;
    }

    /**
     * Return the ID of this data handler.
     *
     * @return string The ID.
     */
    public function getId()
    {
        $id = $this->_driver->getParameters();
        unset($id['user']);
        $id['owner'] = $this->_folder->getOwner();
        $id['folder'] = $this->_folder->getSubpath();
        $id['type'] = $this->getType();
        ksort($id);
        return md5(serialize($id));
    }

    /**
     * Return the ID parameters for this data handler.
     *
     * @return array The ID parameters.
     */
    public function getIdParameters()
    {
        $id = $this->_driver->getParameters();
        unset($id['user']);
        $id['owner'] = $this->_folder->getOwner();
        $id['folder'] = $this->_folder->getSubpath();
        $id['type'] = $this->getType();
        return $id;
    }

    /**
     * Return the data type represented by this object.
     *
     * @return string The type of data this instance handles.
     */
    public function getType()
    {
        if ($this->_type === null) {
            $this->_type = $this->_folder->getType();
        }
        return $this->_type;
    }

    /**
     * Return the data version.
     *
     * @return string The data version.
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * Report the status of this folder.
     *
     * @return Horde_Kolab_Storage_Folder_Stamp The stamp that can be used for
     *                                          detecting folder changes.
     */
    public function getStamp()
    {
        return $this->_driver->getStamp($this->_folder->getPath());
    }

    /**
     * Create a new object.
     *
     * @param array   $object The array that holds the object data.
     * @param boolean $raw    True if the data to be stored has been provided in
     *                        raw format.
     *
     * @return string The ID of the new object or true in case the backend does
     *                not support this return value.
     *
     * @throws Horde_Kolab_Storage_Exception In case an error occured while
     *                                       saving the data.
     */
    public function create($object, $raw = false)
    {
        if (!isset($object['uid'])) {
            $object['uid'] = $this->generateUid();
        }
        return $this->_driver->getParser()
            ->create(
                $this->_folder->getPath(),
                $object,
                array(
                    'type' => $this->getType(),
                    'version' => $this->_version,
                    'raw' => $raw
                )
            );
    }

    /**
     * Modify an existing object.
     *
     * @param array   $object The array that holds the updated object data.
     * @param boolean $raw    True if the data to be stored has been provided in
     *                        raw format.
     *
     * @return string The new backend ID of the modified object or true in case
     *                the backend does not support this return value.
     *
     * @throws Horde_Kolab_Storage_Exception In case an error occured while
     *                                       saving the data.
     */
    public function modify($object, $raw = false)
    {
        if (!isset($object['uid'])) {
            throw new Horde_Kolab_Storage_Exception(
                'The provided object data contains no ID value!'
            );
        }
        try {
            $obid = $this->getBackendId($object['uid']);
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        'The message with ID %s does not exist. This probably means that the Kolab object has been modified by somebody else since you retrieved the object from the backend. Original error: %s'
                    ),
                    $object['uid'],
                    0,
                    $e
                )
            );
        }
        return $this->_driver->getParser()
            ->modify(
                $this->_folder->getPath(),
                $object,
                $obid,
                array(
                    'type' => $this->getType(),
                    'version' => $this->_version,
                    'raw' => $raw
                )
            );
    }

    /**
     * Retrieves the complete message for the given UID.
     *
     * @param string $uid The message UID.
     *
     * @return array The message encapsuled as an array that contains a
     *               Horde_Mime_Headers and a Horde_Mime_Part object.
     */
    public function fetchComplete($uid)
    {
        if (!method_exists($this->_driver, 'fetchComplete')) {
            throw new Horde_Kolab_Storage_Exception(
                'The backend does not support the "fetchComplete" method!'
            );
        }
        return $this->_driver->fetchComplete(
            $this->_folder->getPath(), $uid
        );
    }

    /**
     * Retrieves the body part for the given UID and mime part ID.
     *
     * @param string $uid The message UID.
     * @param string $id  The mime part ID.
     *
     * @return resource The message part as stream resource.
     */
    public function fetchPart($uid, $id)
    {
        if (!method_exists($this->_driver, 'fetchBodypart')) {
            throw new Horde_Kolab_Storage_Exception(
                'The backend does not support the "fetchBodypart" method!'
            );
        }
        return $this->_driver->fetchBodypart(
            $this->_folder->getPath(), $uid, $id
        );
    }

    /**
     * Retrieves the objects for the given UIDs.
     *
     * @param array   $uids The message UIDs.
     * @param boolean $raw  True if the raw format should be returned rather than
     *                      the parsed data.
     *
     * @return array An array of objects.
     */
    public function fetch($uids, $raw = false)
    {
        if (!empty($uids)) {
            return $this->_driver->fetch(
                $this->_folder->getPath(),
                $uids,
                array(
                    'type' => $this->getType(),
                    'version' => $this->_version,
                    'raw' => $raw
                )
            );
        } else {
            return array();
        }
    }

    /**
     * Return the backend ID for the given object ID.
     *
     * @param string $object_uid The object ID.
     *
     * @return string The backend ID for the object.
     */
    public function getBackendId($object_id)
    {
        $by_obid = $this->fetch($this->getStamp()->ids());
        foreach ($by_obid as $obid => $object) {
            if ($object['uid'] == $object_id) {
                return $obid;
            }
        }
        throw new Horde_Kolab_Storage_Exception(
            sprintf('Object ID %s does not exist!', $object_id)
        );
    }

    /**
     * Generate a unique object ID.
     *
     * @return string  The unique ID.
     */
    public function generateUid()
    {
        return strval(new Horde_Support_Uuid());
    }

    /**
     * Check if the given object ID exists.
     *
     * @param string $object_id The object ID.
     *
     * @return boolean True if the ID was found, false otherwise.
     */
    public function objectIdExists($object_id)
    {
        return array_key_exists(
            $object_id, $this->getObjects()
        );
    }

    /**
     * Return the specified object.
     *
     * @param string $object_id The object id.
     *
     * @return array The object data as an array.
     */
    public function getObject($object_id)
    {
        $objects = $this->getObjects();
        if (isset($objects[$object_id])) {
            return $objects[$object_id];
        } else {
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Object ID %s does not exist!', $object_id)
            );
        }
    }

    /**
     * Return the specified attachment.
     *
     * @param string $attachment_id The attachment id.
     *
     * @return resource An open stream to the attachment data.
     */
    public function getAttachment($attachment_id)
    {
        //@todo: implement
    }

    /**
     * Retrieve all object ids in the current folder.
     *
     * @return array The object ids.
     */
    public function getObjectIds()
    {
        return array_keys($this->getObjects());
    }

    /**
     * Retrieve all objects in the current folder.
     *
     * @return array An array of all objects.
     */
    public function getObjects()
    {
        $by_oid  = array();
        $by_obid = $this->fetch($this->getStamp()->ids());
        foreach ($by_obid as $obid => $object) {
            $by_oid[$object['uid']] = $object;
        }
        return $by_oid;
    }

    /**
     * Move the specified message from the current folder into a new
     * folder.
     *
     * @param string $object_id  ID of the message to be moved.
     * @param string $new_folder Target folder.
     *
     * @return NULL
     */
    public function move($object_id, $new_folder)
    {
        if ($this->objectIdExists($object_id)) {
            $uid = $this->getBackendId($object_id);
        } else {
            throw new Horde_Kolab_Storage_Exception(
                sprintf('No such object %s!', $id)
            );
        }
        $this->_driver->moveMessage(
            $uid, $this->_folder->getPath(), $new_folder
        );
    }

    /**
     * Delete the specified objects from this data set.
     *
     * @param array|string $object_ids Id(s) of the object to be deleted.
     *
     * @return NULL
     */
    public function delete($object_ids)
    {
        if (!is_array($object_ids)) {
            $object_ids = array($object_ids);
        }

        $uids = array();
        foreach ($object_ids as $id) {
            if ($this->objectIdExists($id)) {
                $uids[] = $this->getBackendId($id);
            } else {
                throw new Horde_Kolab_Storage_Exception(
                    sprintf('No such object %s!', $id)
                );
            }
        }
        $this->deleteBackendIds($uids);
    }

    /**
     * Delete all objects from this data set.
     *
     * @return NULL
     */
    public function deleteAll()
    {
        $this->delete($this->getObjectIds());
    }

    /**
     * Delete the specified messages from this folder.
     *
     * @param array|string $uids Backend id(s) of the message to be deleted.
     *
     * @return NULL
     */
    public function deleteBackendIds($uids)
    {
        if (!is_array($uids)) {
            $uids = array($uids);
        }
        $this->_driver->deleteMessages($this->_folder->getPath(), $uids);
        $this->_driver->expunge($this->_folder->getPath());
    }

    /**
     * Register a query to be updated if the underlying data changes.
     *
     * @param string                    $name  The query name.
     * @param Horde_Kolab_Storage_Query $query The query to register.
     *
     * @return NULL
     */
    public function registerQuery($name, Horde_Kolab_Storage_Query $query)
    {
        if (!$query instanceOf Horde_Kolab_Storage_Data_Query) {
            throw new Horde_Kolab_Storage_Exception(
                'The provided query is no data query.'
            );
        }
        $this->_queries[$name] = $query;
    }

    /**
     * Synchronize the data information with the information from the backend.
     *
     * @return NULL
     */
    public function synchronize()
    {
        foreach ($this->_queries as $name => $query) {
            $query->synchronize();
        }
    }

    /**
     * Return a registered query.
     *
     * @param string $name The query name.
     *
     * @return Horde_Kolab_Storage_Query The requested query.
     *
     * @throws Horde_Kolab_Storage_Exception In case the requested query does
     *                                       not exist.
     */
    public function getQuery($name = null)
    {
        if ($name === null) {
            $name = self::QUERY_BASE;
        }
        if (isset($this->_queries[$name])) {
            return $this->_queries[$name];
        } else {
            throw new Horde_Kolab_Storage_Exception('No such query!');
        }
    }
}
