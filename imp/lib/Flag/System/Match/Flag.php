<?php
/**
 * This class implements an IMP system flag with matching on IMAP flags.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
abstract class IMP_Flag_System_Match_Flag extends IMP_Flag_Base
{
    /**
     * @param array $data  List of IMAP flags.
     */
    public function match(array $data)
    {
        return false;
    }

}
