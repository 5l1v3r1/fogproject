<?php
/**
 * Snapin management page
 *
 * PHP version 5
 *
 * @category SnapinManagementPage
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Snapin management page
 *
 * @category SnapinManagementPage
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class SnapinManagementPage extends FOGPage
{
    /**
     * Arg types for snapin template
     *
     * @var array
     */
    private static $_argTypes = array(
        'MSI' => array('msiexec.exe','/i','/quiet'),
        'Batch Script' => array('cmd.exe','/c'),
        'Bash Script' => array('/bin/bash'),
        'VB Script' => array('cscript.exe'),
        'Powershell' => array(
            'powershell.exe',
            '-ExecutionPolicy Bypass -NoProfile -File'
        ),
        'Mono' => array('mono'),
    );
    /**
     * Template for non-pack.
     *
     * @var string
     */
    private static $_template1;
    /**
     * Template for pack.
     *
     * @var string
     */
    private static $_template2;
    /**
     * The node this page operates off of.
     *
     * @var string
     */
    public $node = 'snapin';
    /**
     * Initializes the snapin page class
     *
     * @param string $name the name to pass
     *
     * @return void
     */
    public function __construct($name = '')
    {
        /**
         * The real name not using our name passer.
         */
        $this->name = 'Snapin Management';
        /**
         * Pull in the FOG Page class items.
         */
        parent::__construct($name);
        /**
         * Generate our snapin arg templates.
         */
        /**
         * Start a new buffer (last one ended anyway)
         * to create our template non-pack.
         */
        ob_start();
        printf(
            '<select name="argTypes" id="argTypes">'
            . '<option value="">- %s -</option>',
            _('Please select an option')
        );
        foreach (self::$_argTypes as $type => &$cmd) {
            printf(
                '<option value="%s" rwargs="%s" args="%s">%s</option>',
                $cmd[0],
                $cmd[1],
                $cmd[2],
                $type
            );
            unset($cmd);
        }
        echo '</select>';
        self::$_template1 = ob_get_clean();
        /**
         * Store the pack based template.
         */
        self::$_template2 = $this->_maker();
        /**
         * Get our nicer names.
         */
        global $id;
        global $sub;
        if ($id) {
            /**
             * The other sub menu items.
             */
            $this->subMenu = array(
                "$this->linkformat#snap-gen" => self::$foglang['General'],
                "$this->linkformat#snap-storage" => sprintf(
                    '%s %s',
                    self::$foglang['Storage'],
                    self::$foglang['Group']
                ),
                $this->membership => self::$foglang['Membership'],
                $this->delformat => self::$foglang['Delete'],
            );
            /**
             * The notes for this item.
             */
            $this->notes = array(
                self::$foglang['Snapin'] => $this->obj->get('name'),
                self::$foglang['File'] => $this->obj->get('file'),
                _('Filesize') => $this->formatByteSize($this->obj->get('size')),
            );
        }
        /**
         * Allow custom hooks/changes to: Submenu data via.
         *
         * Menu, submenu, id, notes, the main object,
         * linkformat, delformat, and membership information.
         */
        self::$HookManager->processEvent(
            'SUB_MENULINK_DATA',
            array(
                'menu' => &$this->menu,
                'submenu' => &$this->subMenu,
                'id' => &$this->id,
                'notes' => &$this->notes,
                'object' => &$this->obj,
                'linkformat' => &$this->linkformat,
                'delformat' => &$this->delformat,
                'membership' => &$this->membership
            )
        );
        /**
         * The header data for list/search.
         */
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkbox" '
            . 'class="toggle-checkboxAction"/>',
            _('Snapin Name'),
            _('Is Pack'),
            _('Storage Group'),
        );
        /**
         * The template for the list/search elements.
         */
        $this->templates = array(
            '<input type="checkbox" name="snapin[]" value="${id}" '
            . 'class="toggle-action"/>',
            sprintf(
                '<a href="?node=%s&sub=edit&%s=${id}" title="%s">${name}</a>',
                $this->node,
                $this->id,
                _('Edit')
            ),
            '${packtype}',
            '${storageGroup}',
        );
        /**
         * The attributes for the table items.
         */
        $this->attributes = array(
            array('class'=>'l filter-false','width'=>16),
            array(),
            array('class'=>'c','width'=>50),
            array('class'=>'r'),
        );
        /**
         * Lambda function to manage the output
         * of searched/listed items.
         *
         * @param Snapin $Snapin the snapin item.
         *
         * @return void
         */
        self::$returnData = function (&$Snapin) {
            /**
             * If the snapin isn't valid return immediately.
             */
            if (!$Snapin->isValid()) {
                return;
            }
            /**
             * Stores the items in a nicer name.
             */
            /**
             * The id.
             */
            $id = $Snapin->get('id');
            /**
             * The name.
             */
            $name = $Snapin->get('name');
            /**
             * The description.
             */
            $description = $Snapin->get('description');
            /**
             * The file name.
             */
            $file = $Snapin->get('file');
            /**
             * Tell if packtype is true or not.
             */
            if ($Snapin->get('packtype') < 0) {
                $packtype = _('No');
            } else {
                $packtype = _('Yes');
            }
            /**
             * The storage group name.
             */
            $storageGroup = $Snapin->getStorageGroup()->get('name');
            /**
             * Store the data.
             */
            $this->data[] = array(
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'file' => $file,
                'packtype' => $packtype,
                'storageGroup' => $storageGroup,
            );
            /**
             * Cleanup.
             */
            unset(
                $id,
                $name,
                $description,
                $file,
                $packtype,
                $Snapin
            );
        };
    }
    /**
     * Generates the selector for Snapin Packs.
     *
     * @return void
     */
    private function _maker()
    {
        $args = array(
            'MSI' => array(
                'msiexec.exe',
                '/i &quot;[FOG_SNAPIN_PATH]\MyMSI.msi&quot;'
            ),
            'MSI + MST' => array(
                'msiexec.exe',
                '/i &quot;[FOG_SNAPIN_PATH]\MyMST.mst&quot;'
            ),
            'Batch Script' => array(
                'cmd.exe',
                '/c &quot;[FOG_SNAPIN_PATH]\MyScript.bat&quot;'
            ),
            'Bash Script' => array(
                '/bin/bash',
                '&quot;[FOG_SNAPIN_PATH]/MyScript.sh&quot;'
            ),
            'VB Script' => array(
                'cscript.exe',
                '&quot;[FOG_SNAPIN_PATH]\MyScript.vbs&quot;'
            ),
            'PowerShell Script' => array(
                'powershell.exe',
                '-ExecutionPolicy Bypass -File &quot;'
                .'[FOG_SNAPIN_PATH]\MyScript.ps1&quot;'
            ),
            'EXE' => array(
                '[FOG_SNAPIN_PATH]\MyFile.exe'
            ),
            'Mono' => array(
                'mono',
                '&quot;[FOG_SNAPIN_PATH]/MyFile.exe&quot;'
            ),
        );
        ob_start();
        printf(
            '<select id="packTypes"><option value="">- %s -</option>',
            _('Please select an option')
        );
        foreach ($args as $type => &$cmd) {
            printf(
                '<option file="%s" args="%s">%s</option>',
                $cmd[0],
                (
                    isset($cmd[1]) ?
                    $cmd[1] :
                    ''
                ),
                $type
            );
            unset($cmd);
        }
        echo '</select>';
        return ob_get_clean();
    }
    /**
     * The base element displayed when first going to this page.
     *
     * @return void
     */
    public function index()
    {
        $this->title = _('All Snapins');
        /**
         * If data return is enabled, test if the count is
         * greater than the data return.
         *
         * This will redirect to the search page, and only
         * displays for "list" view elements.
         *
         * If the user has implicitely chosen list, it is not
         * used.
         */
        if ($_SESSION['DataReturn'] > 0
            && $_SESSION['SnapinCount'] > $_SESSION['DataReturn']
            && $sub != 'list'
        ) {
            $this->redirect(
                sprintf(
                    '?node=%s&sub=search',
                    $this->node
                )
            );
        }
        /**
         * Ensure our data is "clean" first.
         */
        $this->data = array();
        /**
         * Store our objects
         */
        $Snapins = self::getClass('SnapinManager')->find();
        /**
         * Iterate our objects.
         */
        array_walk($Snapins, self::$returnData);
        /**
         * Cleanup snapins.
         */
        unset($Snapins);
        /**
         * Create our hook to hook/change items on data.
         */
        self::$HookManager
            ->processEvent(
                'SNAPIN_DATA',
                array(
                    'headerData' => &$this->headerData,
                    'data' => &$this->data,
                    'templates' => &$this->templates,
                    'attributes' => &$this->attributes
                )
            );
        /**
         * Send to the display.
         */
        $this->render();
        /**
         * Cleanup all the rest.
         */
        unset(
            $this->headerData,
            $this->data,
            $this->templates,
            $this->attributes
        );
    }
    /**
     * The form to display when adding a new snapin
     * definition.
     *
     * @return void
     */
    public function add()
    {
        /**
         * Title of inital/general element.
         */
        $this->title = _('New Snapin');
        /**
         * The table attributes.
         */
        $this->attributes = array(
            array(),
            array(),
        );
        /**
         * The table template.
         */
        $this->templates = array(
            '${field}',
            '${input}',
        );
        /**
         * Set the storage group to pre-select.
         */
        if (isset($_REQUEST['storagegroup'])
            && is_numeric($_REQUEST['storagegroup'])
            && $_REQUEST['storagegroup'] > 0
        ) {
            $sgID = $_REQUEST['storagegroup'];
        } else {
            $sgID = @min(self::getSubObjectIDs('StorageGroup'));
        }
        /**
         * Set our storage group object.
         */
        $StorageGroup = new StorageGroup($sgID);
        $StorageGroups = self::getClass('StorageGroupManager')
            ->buildSelectBox(
                $sgID,
                '',
                'id'
            );
        /**
         * We'll get the files associated with the selected
         * group.
         */
        /**
         * Reset our "selected" item for our option list.
         */
        self::$selected = '';
        /**
         * If the snapin file exists, set selected item
         * to this value.
         */
        if (isset($_REQUEST['snapinfileexist'])) {
            self::$selected = basename($_REQUEST['snapinfileexist']);
        }
        /**
         * Initialize the filelist.
         */
        $filelist = array();
        /**
         * Get the master storage node.
         */
        $StorageNode = $StorageGroup->getMasterStorageNode();
        /**
         * Get the files on this node.
         */
        $filelist = $StorageNode->get('snapinfiles');
        /**
         * Sort our files nicely.
         *
         * Naturally sort as snapins in form:
         * 03x, 01x, 02x, or only numerically named
         * should present in human natural order.
         */
        natcasesort($filelist);
        /**
         * Filter the list.
         */
        $filelist = array_filter($filelist);
        /**
         * Ensure we remove any duplicates.
         */
        $filelist = array_unique($filelist);
        /**
         * Re-order the array.
         */
        $filelist = array_values($filelist);
        /**
         * Buffer the select box.
         */
        ob_start();
        /**
         * Build our select box based on this file list.
         */
        array_map(self::$buildSelectBox, $filelist);
        /**
         * Create our listing and store in a variable.
         */
        $selectFiles = sprintf(
            '<select class="snapinfileexist-input cmdlet3" name='
            . '"snapinfileexist"><span class="lightColor">'
            . '<option value="">- %s -</option>%s</select>',
            _('Please select an option'),
            ob_get_clean()
        );
        /**
         * Setup the fields to be used to display.
         */
        $fields = array(
            _('Snapin Name') => sprintf(
                '<input class="snapinname-input" type='
                . '"text" name="name" value="%s"/>',
                $_REQUEST['name']
            ),
            _('Snapin Description') => sprintf(
                '<textarea class="snapindescription-input" name='
                . '"description" rows="8" cols="40">%s</textarea>',
                $_REQUEST['description']
            ),
            _('Storage Group') => $StorageGroups,
            _('Snapin Type') => sprintf(
                '<select class="snapinpack-input" name='
                . '"packtype" id="snapinpack"><option value='
                . '"0"%s>%s</option><option value="1"%s>%s</option></select>',
                (
                    !$_REQUEST['packtype']
                    || !isset($_REQUEST['packtype']) ?
                    ' selected' :
                    ''
                ),
                _('Normal Snapin'),
                (
                    isset($_REQUEST['packtype'])
                    && $_REQUEST['packtype'] > 0 ?
                    ' selected' :
                    ''
                ),
                _('Snapin Pack')
            ),
            sprintf(
                '<span class="packnotemplate">%s</span>'
                . '<span class="packtemplate">%s</span>',
                _('Snapin Template'),
                _('Snapin Pack Template')
            ) => sprintf(
                '<span class="packnotemplate">%s</span>'
                . '<span class="packtemplate">%s</span>',
                self::$_template1,
                self::$_template2
            ),
            sprintf(
                '<span class="packnochangerw">%s</span>'
                . '<span class="packchangerw">%s</span>',
                _('Snapin Run With'),
                _('Snapin Pack File')
            ) => sprintf(
                '<input class="snapinrw-input cmdlet1" type='
                . '"text" name="rw" value="%s"/>',
                $_REQUEST['rw']
            ),
            sprintf(
                '<span class="packnochangerwa">%s</span>'
                . '<span class="packchangerwa">%s</span>',
                _('Snapin Run With Argument'),
                _('Snapin Pack Arguments')
            ) => sprintf(
                '<input class="snapinrwa-input cmdlet2" type='
                . '"text" name="rwa" value="%s"/>',
                $_REQUEST['rwa']
            ),
            sprintf(
                '%s <span class="lightColor">%s:%s</span>',
                _('Snapin File'),
                _('Max Size'),
                ini_get('post_max_size')
            ) => sprintf(
                '<input class="snapinfile-input cmdlet3" name='
                . '"snapin" value="%s" type="file"/>',
                $_FILES['snapin']['name']
            ),
            (
                count($filelist) > 0 ?
                _('Snapin File (exists)') :
                ''
            ) => (
                count($filelist) > 0 ?
                $selectFiles :
                ''
            ),
            sprintf(
                '<span class="packhide">%s</span>',
                _('Snapin Arguments')
            ) => sprintf(
                '<span class="packhide"><input class='
                . '"snapinargs-input cmdlet4" type="text" name='
                . '"args" value="%s"/></span>',
                $_REQUEST['args']
            ),
            _('Snapin Enabled') => sprintf(
                '<input class="snapinenabled-input" type='
                . '"checkbox" name='
                . '"isEnabled" checked/>'
            ),
            _('Snapin Arguments Hidden') => sprintf(
                '<input class="snapinhidden-input" type='
                . '"checkbox" name="isHidden" value="1"/>'
            ),
            _('Snapin Timeout (seconds)') => sprintf(
                '<input class="snapintimeout-input" type='
                . '"text" name="timeout" value="0"/>'
            ),
            _('Replicate?') => sprintf(
                '<input class='
                . '"snapinreplicate-input" type="checkbox" name='
                . '"toReplicate" value="1" checked/>'
            ),
            _('Reboot after install') => sprintf(
                '<input class="snapinreboot-input action" type='
                . '"radio" name="action" value="reboot"/>'
            ),
            _('Shutdown after install') => sprintf(
                '<input class="snapinshutdown-input action" type='
                . '"radio" name="action" value="shutdown"/>'
            ),
            sprintf(
                '%s<br/><small>%s</small>',
                _('Snapin Command'),
                _('read-only')
            ) => '<textarea class="snapincmd" readonly></textarea>',
            '&nbsp;' => sprintf(
                '<input name="add" type="submit" value="%s"/>',
                _('Add')
            )
        );
        unset($files, $selectFiles);
        printf(
            '<form method="post" action"%s" enctype="multipart/form-data">',
            $this->formAction
        );
        foreach ((array)$fields as $field => &$input) {
            $this->data[] = array(
                'field' => $field,
                'input' => $input,
            );
            unset($input);
        }
        unset($fields);
        self::$HookManager
            ->processEvent(
                'SNAPIN_ADD',
                array(
                    'headerData' => &$this->headerData,
                    'data' => &$this->data,
                    'templates' => &$this->templates,
                    'attributes' => &$this->attributes
                )
            );
        $this->render();
        echo '</form>';
        unset(
            $this->headerData,
            $this->templates,
            $this->attributes,
            $this->data
        );
    }
    /**
     * Actually sibmit the creation of the snapin.
     *
     * @return void
     */
    public function addPost()
    {
        self::$HookManager->processEvent('SNAPIN_ADD_POST');
        try {
            $name = trim($_REQUEST['name']);
            if (!$name) {
                throw new Exception(_('A snapin name is required!'));
            }
            if (self::getClass('SnapinManager')->exists($name)) {
                throw new Exception(_('A snapin already exists with this name!'));
            }
            if (empty($_REQUEST['storagegroup'])) {
                throw new Exception(_('A Storage Group is required!'));
            }
            $snapinfile = trim(basename($_REQUEST['snapinfileexist']));
            if (!$snapinfile && $_FILES['snapin']['error'] > 0) {
                throw new UploadException($_FILES['snapin']['error']);
            }
            $uploadfile = trim(basename($_FILES['snapin']['name']));
            if ($uploadfile) {
                $snapinfile = $uploadfile;
            }
            if (!$snapinfile) {
                throw new Exception(
                    sprintf(
                        '%s, %s, %s!',
                        _('A file'),
                        _('either already selected or uploaded'),
                        _('must be specified')
                    )
                );
            }
            if (preg_match('#ssl#i', $snapinfile)) {
                throw new Exception(
                    sprintf(
                        '%s, %s.',
                        _('Please choose a different name'),
                        _('this one is reserved for FOG')
                    )
                );
            }
            $snapinfile = preg_replace('/[^-\w\.]+/', '_', $snapinfile);
            $StorageGroup = new StorageGroup($_REQUEST['storagegroup']);
            $StorageNode = $StorageGroup->getMasterStorageNode();
            $src = sprintf(
                '%s/%s',
                dirname($_FILES['snapin']['tmp_name']),
                basename($_FILES['snapin']['tmp_name'])
            );
            $dest = sprintf(
                '/%s/%s',
                trim($StorageNode->get('snapinpath'), '/'),
                $snapinfile
            );
            $hash = '';
            $size = 0;
            if ($uploadfile && file_exists($src)) {
                $hash = hash_file('sha512', $src);
                $size = self::getFilesize($src);
            }
            set_time_limit(0);
            if ($uploadfile) {
                self::$FOGFTP
                    ->set('host', $StorageNode->get('ip'))
                    ->set('username', $StorageNode->get('user'))
                    ->set('password', $StorageNode->get('pass'));
                if (!self::$FOGFTP->connect()) {
                    throw new Exception(
                        sprintf(
                            '%s: %s %s',
                            _('Storage Node'),
                            $StorageNode->get('ip'),
                            _('FTP Connection has failed')
                        )
                    );
                }
                if (!self::$FOGFTP->chdir($StorageNode->get('snapinpath'))) {
                    if (!self::$FOGFTP->mkdir($StorageNode->get('snapinpath'))) {
                        throw new Exception(
                            sprintf(
                                '%s, %s.',
                                _('Failed to add snapin'),
                                _('unable to locate snapin directory')
                            )
                        );
                    }
                }
                if (is_file($dest)) {
                    self::$FOGFTP->delete($dest);
                }
                if (!self::$FOGFTP->put($dest, $src)) {
                    throw new Exception(_('Failed to add/update snapin file'));
                }
                self::$FOGFTP
                    ->chmod(0755, $dest)
                    ->close();
            }
            $reboot = false;
            $shutdown = false;
            if (isset($_REQUEST['action'])) {
                switch ($_REQUEST['action']) {
                case 'reboot':
                    $reboot = true;
                    break;
                case 'shutdown':
                    $shutdown = true;
                    break;
                }
            }
            $Snapin = self::getClass('Snapin')
                ->set('name', $name)
                ->set('packtype', $_REQUEST['packtype'])
                ->set('description', $_REQUEST['description'])
                ->set('file', $snapinfile)
                ->set('hash', $hash)
                ->set('size', $size)
                ->set('args', $_REQUEST['args'])
                ->set('reboot', $reboot)
                ->set('shutdown', $shutdown)
                ->set('runWith', $_REQUEST['rw'])
                ->set('runWithArgs', $_REQUEST['rwa'])
                ->set('isEnabled', isset($_REQUEST['isEnabled']))
                ->set('toReplicate', isset($_REQUEST['toReplicate']))
                ->set('hide', isset($_REQUEST['isHidden']))
                ->set('timeout', $_REQUEST['timeout'])
                ->addGroup($_REQUEST['storagegroup']);
            if (!$Snapin->save()) {
                throw new Exception(_('Add snapin failed!'));
            }
            /**
             * During snapin creation we only allow a single group anyway.
             * This will set it to be the primary master.
             */
            $Snapin->setPrimaryGroup($_REQUEST['storagegroup']);
            self::$HookManager
                ->processEvent(
                    'SNAPIN_ADD_SUCCESS',
                    array(
                        'Snapin' => &$Snapin
                    )
                );
            $this->setMessage(_('Snapin created'));
            $this->redirect(
                sprintf(
                    '?node=%s&sub=edit&id=%s',
                    $this->node,
                    $Snapin->get('id')
                )
            );
        } catch (Exception $e) {
            self::$FOGFTP->close();
            self::$HookManager->processEvent(
                'SNAPIN_ADD_FAIL',
                array(
                    'Snapin' => &$Snapin
                )
            );
            $this->setMessage($e->getMessage());
            $this->redirect($this->formAction);
        }
    }
    /**
     * Edit this image
     *
     * @return void
     */
    public function edit()
    {
        /**
         * Display our edit title.
         */
        $this->title = sprintf('%s: %s', _('Edit'), $this->obj->get('name'));
        unset($this->headerData);
        $this->attributes = array(
            array(),
            array(),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        self::$selected = $this->obj->get('file');
        $filelist = array();
        array_map(function (&$StorageNode) use (&$filelist) {
            if (!$StorageNode->isValid()) {
                return;
            }
            $filelist = array_merge((array)$filelist, (array)$StorageNode->get('snapinfiles'));
            unset($StorageNode);
        }, self::getClass('StorageNodeManager')->find(array('isMaster'=>1, 'isEnabled'=>1)));
        natcasesort($filelist);
        $filelist = array_values(array_filter(array_unique((array)$filelist)));
        ob_start();
        array_map(self::$buildSelectBox, $filelist);
        $selectFiles = sprintf('<select class="snapinfileexist-input cmdlet3" name="snapinfileexist"><span class="lightColor"><option value="">- %s -</option>%s</select>', _('Please select an option'), ob_get_clean());
        ob_start();
        printf('<select name="argTypes" id="argTypes"><option value="">- %s -</option>', _('Please select an option'));
        array_walk(self::$_argTypes, function (&$cmd, &$type) {
            printf('<option value="%s" rwargs="%s" args="%s">%s</option>', $cmd[0], $cmd[1], $cmd[2], $type);
        });
        echo '</select>';
        $template1 = ob_get_clean();
        $template2 = $this->_maker();
        $fields = array(
            _('Snapin Name') => sprintf('<input class="snapinname-input" type="text" name="name" value="%s"/>', $this->obj->get('name')),
            _('Snapin Type')=> sprintf('<select class="snapinpack-input" name="packtype" id="snapinpack"><option value="0"%s>%s</option><option value="1"%s>%s</option></select>', !$this->obj->get('packtype') ? ' selected' : '', _('Normal Snapin'), $this->obj->get('packtype') ? ' selected' : '', _('Snapin Pack')),
            _('Snapin Description') => sprintf('<textarea class="snapindescription-input" name="description" rows="8" cols="40">%s</textarea>', $this->obj->get('description')),
            sprintf('<span class="packnotemplate">%s</span><span class="packtemplate">%s</span>', _('Snapin Template'), _('Snapin Pack Template')) => sprintf('<span class="packnotemplate">%s</span><span class="packtemplate">%s</span>', $template1, $template2),
            sprintf('<span class="packnochangerw">%s</span><span class="packchangerw">%s</span>', _('Snapin Run With'), _('Snapin Pack File')) => sprintf('<input class="snapinrw-input cmdlet1" type="text" name="rw" value="%s"/>', $this->obj->get('runWith')),
            sprintf('<span class="packnochangerwa">%s</span><span class="packchangerwa">%s</span>', _('Snapin Run With Argument'), _('Snapin Pack Arguments')) => sprintf('<input class="snapinrwa-input cmdlet2" type="text" name="rwa" value="%s"/>', $this->obj->get('runWithArgs')),
            sprintf('%s <span class="lightColor">%s:%s</span>', _('Snapin File'), _('Max Size'), ini_get('post_max_size')) => sprintf('<label id="uploader" for="snapin-uploader">%s<a href="#" id="snapin-upload"> <i class="fa fa-arrow-up noBorder"></i></a></label>', basename($this->obj->get('file'))),
            (count($filelist) > 0 ? _('Snapin File (exists)') : '') => (count($filelist) > 0 ? $selectFiles : ''),
            sprintf('<span class="packhide">%s</span>', _('Snapin Arguments')) => sprintf('<span class="packhide"><input class="snapinargs-input cmdlet4" type="text" name="args" value="%s"/></span>', $this->obj->get('args')),
            _('Protected') => sprintf('<input class="snapinprotected-input" type="checkbox" name="protected_snapin" value="1"%s/>', $this->obj->get('protected') ? ' checked' : ''),
            _('Reboot after install') => sprintf('<input class="snapinreboot-input action" type="radio" name="action" value="reboot"%s/>', $this->obj->get('reboot') ? ' checked' : ''),
            _('Shutdown after install') => sprintf('<input class="snapinreboot-input action" type="radio" name="action" value="shutdown"%s/>', $this->obj->get('shutdown') ? ' checked' : ''),
            _('Snapin Enabled') => sprintf('<input class="snapinenabled-input" type="checkbox" name="isEnabled" value="1"%s/>', $this->obj->get('isEnabled') ? ' checked' : ''),
            _('Replicate?') => sprintf('<input class="snapinreplicate-input" type="checkbox" name="toReplicate" value="1"%s/>', $this->obj->get('toReplicate') ? ' checked' : ''),
            _('Snapin Arguments Hidden?') => sprintf('<input class="snapinhidden-input" type="checkbox" name="isHidden" value="1"%s/>', $this->obj->get('hide') ? ' checked' : ''),
            _('Snapin Timeout (seconds)') => sprintf('<input class="snapintimeout-input" type="text" name="timeout" value="%s"/>', $this->obj->get('timeout')),
            sprintf('%s<br/><small>%s</small>', _('Snapin Command'), _('read-only')) => '<textarea class="snapincmd" readonly></textarea>',
            sprintf('%s <small>%s</small><br/><small>%s</small>', _('File Hash'), 'sha512', _('read-only')) => sprintf('<textarea readonly>%s</textarea>', $this->obj->get('hash')),
            '' => sprintf('<input name="update" type="submit" value="%s"/>', _('Update')),
        );
        echo '<div id="tab-container"><!-- General --><div id="snap-gen">';
        echo '<form method="post" action="'.$this->formAction.'&tab=snap-gen" enctype="multipart/form-data">';
        foreach ((array)$fields as $field => &$input) {
            $this->data[] = array(
                'field'=>$field,
                'input'=>$input,
            );
        }
        unset($input);
        self::$HookManager->processEvent('SNAPIN_EDIT', array('headerData'=>&$this->headerData, 'data'=>&$this->data, 'templates'=>&$this->templates, 'attributes'=>&$this->attributes));
        printf('<form method="post" action="%s&tab=snap-gen" enctype="multipart/form-data">', $this->formAction);
        $this->render();
        echo '</form></div>';
        unset($this->data);
        echo "<!-- Snapin Groups -->";
        echo '<div id="snap-storage">';
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkboxsnapin1" class="toggle-checkbox1"/>',
            _('Storage Group Name'),
        );
        $this->templates = array(
            '<input type="checkbox" name="storagegroup[]" value="${storageGroup_id}" class="toggle-snapin1"/>',
            '${storageGroup_name}',
        );
        $this->attributes = array(
            array('class'=>'l filter-false','width'=>16),
            array(),
        );
        $storagegroups = function (&$StorageGroup) {
            if (!$StorageGroup->isValid()) {
                return;
            }
            $this->data[] = array(
                'storageGroup_id' => $StorageGroup->get('id'),
                'storageGroup_name' => $StorageGroup->get('name'),
                'is_primary' => ($this->obj->getPrimaryGroup($StorageGroup->get('id')) ? ' checked' : ''),
            );
        };
        array_map($storagegroups, self::getClass('StorageGroupManager')->find(array('id'=>$this->obj->get('storagegroupsnotinme'))));
        if (count($this->data) > 0) {
            self::$HookManager->processEvent('SNAPIN_GROUP_ASSOC', array('headerData'=>&$this->headerData, 'data'=>&$this->data, 'templates'=>&$this->templates, 'attributes'=>&$this->attributes));
            printf(
                '<p class="c"><label for="groupMeShow">%s&nbsp;&nbsp;<input type="checkbox" name="groupMeShow" id="groupMeShow"/></label><div id="groupNotInMe"><form method="post" action="%s&tab=snap-storage"><h2>%s %s</h2><p class="c">%s</p>',
                _('Check here to see groups not assigned with this snapin'),
                $this->formAction,
                _('Modify group association for'),
                $this->obj->get('name'),
                _('Add snapin to groups')
            );
            $this->render();
            printf('<br/><input type="submit" value="%s"/></form></div></p>', _('Add Snapin to Group(s)'));
        }
        unset($this->data);
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction"/>',
            '',
            _('Storage Group Name'),
        );
        $this->attributes = array(
            array('width'=>16,'class'=>'l filter-false'),
            array('width'=>22,'class'=>'l filter-false'),
            array('class'=>'r'),
        );
        $this->templates = array(
            '<input type="checkbox" class="toggle-action" name="storagegroup-rm[]" value="${storageGroup_id}"/>',
            sprintf('<input class="primary" type="radio" name="primary" id="group${storageGroup_id}" value="${storageGroup_id}"${is_primary}/><label for="group${storageGroup_id}" class="icon icon-hand" title="%s">&nbsp;</label>', _('Primary Group Selector')),
            '${storageGroup_name}',
        );
        array_map($storagegroups, self::getClass('StorageGroupManager')->find(array('id'=>$this->obj->get('storagegroups'))));
        self::$HookManager->processEvent('SNAPIN_EDIT_GROUP', array('headerData'=>&$this->headerData, 'data'=>&$this->data, 'templates'=>&$this->templates, 'attributes'=>&$this->attributes));
        printf('<form method="post" action="%s&tab=snap-storage">', $this->formAction);
        $this->render();
        if (count($this->data) > 0) {
            printf('<p class="c"><input name="update" type="submit" value="%s"/>&nbsp;<input name="deleteGroup" type="submit" value="%s"/></p>', _('Update Primary Group'), _('Deleted selected group associations'));
        }
        echo '</form></div></div>';
    }
    public function editPost()
    {
        self::$HookManager->processEvent('SNAPIN_EDIT_POST', array('Snapin'=>&$this->obj));
        try {
            switch ($_REQUEST['tab']) {
            case 'snap-gen':
                $snapinName = trim($_REQUEST['name']);
                if (!$snapinName) {
                    throw new Exception(_('Please enter a name to give this Snapin'));
                }
                if ($snapinName != $this->obj->get('name') && $this->obj->getManager()->exists($snapinName)) {
                    throw new Exception(_('Snapin with that name already exists'));
                }
                $snapinfile = trim(basename($_REQUEST['snapinfileexist']));
                $uploadfile = trim(basename($_FILES['snapin']['name']));
                if ($uploadfile) {
                    $snapinfile = $uploadfile;
                }
                if (!$snapinfile) {
                    throw new Exception(_('A file to use for the snapin must be either uploaded or chosen from the already present list'));
                }
                $snapinfile = preg_replace('/[^-\w\.]+/', '_', $snapinfile);
                $StorageNode = $this->obj->getStorageGroup()->getMasterStorageNode();
                if (!$snapinfile && $_FILES['snapin']['error'] > 0) {
                    throw new UploadException($_FILES['snapin']['error']);
                }
                $src = sprintf('%s/%s', dirname($_FILES['snapin']['tmp_name']), basename($_FILES['snapin']['tmp_name']));
                set_time_limit(0);
                if ($uploadfile && file_exists($src)) {
                    $hash = hash_file('sha512', $src);
                    $size = self::getFilesize($src);
                } else {
                    $hash = '';
                    $size = 0;
                }
                $dest = sprintf('/%s/%s', trim($StorageNode->get('snapinpath'), '/'), $snapinfile);
                if ($uploadfile) {
                    self::$FOGFTP
                        ->set('host', $StorageNode->get('ip'))
                        ->set('username', $StorageNode->get('user'))
                        ->set('password', $StorageNode->get('pass'));
                    if (!self::$FOGFTP->connect()) {
                        throw new Exception(sprintf('%s: %s: %s %s: %s %s', _('Storage Node'), $StorageNode->get('ip'), _('FTP connection has failed')));
                    }
                    if (!self::$FOGFTP->chdir($StorageNode->get('snapinpath'))) {
                        if (!self::$FOGFTP->mkdir($StorageNode->get('snapinpath'))) {
                            throw new Exception(_('Failed to add snapin, unable to locate snapin directory.'));
                        }
                    }
                    if (is_file($dest)) {
                        self::$FOGFTP->delete($dest);
                    }
                    if (!self::$FOGFTP->put($dest, $src)) {
                        throw new Exception(_('Failed to add/update snapin file'));
                    }
                    self::$FOGFTP
                        ->chmod(0755, $dest)
                        ->close();
                }
                $this->obj
                    ->set('name', $snapinName)
                    ->set('packtype', $_REQUEST['packtype'])
                    ->set('description', $_REQUEST['description'])
                    ->set('file', $snapinfile)
                    ->set('args', $_REQUEST['args'])
                    ->set('hash', $hash)
                    ->set('size', $size)
                    ->set('reboot', (isset($_REQUEST['action']) && $_REQUEST['action'] === 'reboot'))
                    ->set('shutdown', (string)intval((isset($_REQUEST['action']) && $_REQUEST['action'] === 'shutdown')))
                    ->set('runWith', $_REQUEST['rw'])
                    ->set('runWithArgs', $_REQUEST['rwa'])
                    ->set('protected', isset($_REQUEST['protected_snapin']))
                    ->set('isEnabled', (string)intval(isset($_REQUEST['isEnabled'])))
                    ->set('toReplicate', (string)intval(isset($_REQUEST['toReplicate'])))
                    ->set('hide', (string)intval(isset($_REQUEST['isHidden'])))
                    ->set('timeout', $_REQUEST['timeout']);
                break;
            case 'snap-storage':
                $this->obj->addGroup($_REQUEST['storagegroup']);
                if (isset($_REQUEST['update'])) {
                    $this->obj->setPrimaryGroup($_REQUEST['primary']);
                }
                if (isset($_REQUEST['deleteGroup'])) {
                    if (count($this->obj->get('storagegroups')) < 2) {
                        throw new Exception(_('Snapin must be assigned to one Storage Group'));
                    }
                    $this->obj->removeGroup($_REQUEST['storagegroup-rm']);
                }
                break;
            }
            if (!$this->obj->save()) {
                throw new Exception(_('Snapin update failed'));
            }
            self::$HookManager
                ->processEvent(
                    'SNAPIN_UPDATE_SUCCESS',
                    array(
                        'Snapin' => &$this->obj
                    )
                );
            $this->setMessage(_('Snapin updated'));
            $this->redirect($this->formAction);
        } catch (Exception $e) {
            self::$FOGFTP->close();
            self::$HookManager
                ->processEvent(
                    'SNAPIN_UPDATE_FAIL',
                    array(
                        'Snapin' => &$this->obj
                    )
                );
            $this->setMessage($e->getMessage());
            $this->redirect($this->formAction);
        }
    }
}
