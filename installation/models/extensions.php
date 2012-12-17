<?php
/**
 * @package    Joomla.Installation
 *
 * @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @since      3.0.x
 */

defined('_JEXEC') or die;

jimport('joomla.updater.update');

/**
 * Extension Installer model for the Joomla Core Installer.
 *
 * @package  Joomla.Installation
 * @since    3.0
 */
class InstallationModelExtensions extends JModelLegacy
{
	/**
	 * @var    array  Extensions description
	 * @since  3.0
	 */
	protected $data = null;

	/**
	 * @var    integer  Total number of extensions installed
	 * @since  3.0
	 */
	protected $extlist = null;

	/**
	 * Constructor
	 *
	 * Deletes the default installation config file and recreates it with the good config file.
	 *
	 * @since  3.0
	 */
	public function __construct()
	{
		// Overrides application config and set the configuration.php file so tokens and database works
		JFactory::$config = null;
		JFactory::getConfig(JPATH_SITE . '/configuration.php');
		JFactory::$session = null;

		parent::__construct();
	}

	/**
	 * Generate a list of package choices to install in the Joomla CMS
	 *
	 * @return  boolean  True if successful
	 *
	 * @since   3.0
	 */
	public function getItems()
	{
		$updater = JUpdater::getInstance();

		/*
		 * The following function uses extension_id 700, which is the CMS Files Extension.
		 * In #__update_sites_extensions you should have 700 linked to the Extensions List
		 */
		$updater->findUpdates(array(700), 0);

		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true);

		// Select the required fields from the updates table
		$query->select('update_id, name, version')
			->from('#__updates')
			->where('update_site_id = 4')
			->order('name');

		$db->setQuery($query);
		$list = $db->loadObjectList();

		if (!$list || $list instanceof Exception)
		{
			$list = array();
		}

		return $list;
	}

	/**
	 * Method that installs in Joomla! the selected packages
	 *
	 * @param   array  $lids  list of the update_id value of the packages to install
	 *
	 * @return  boolean True if successful
	 */
	public function install($lids)
	{
		$app       = JFactory::getApplication();
		$installer = JInstaller::getInstance();

		// Loop through every selected package
		foreach ($lids as $id)
		{
			// Loads the update database object that represents the package
			$extension = JTable::getInstance('update');
			$extension->load($id);

			// Get the url to the XML manifest file of the selected package
			$remote_manifest = $this->getPackageManifest($id);

			if (!$remote_manifest)
			{
				// Could not find the url, the information in the update server may be corrupt
				$message = JText::sprintf('INSTL_DEFAULTLANGUAGE_COULD_NOT_INSTALL_LANGUAGE', $extension->name);
				$message .= ' ' . JText::_('INSTL_DEFAULTLANGUAGE_TRY_LATER');
				$app->enqueueMessage($message);
				continue;
			}

			// Based on the package XML manifest get the url of the package to download
			$package_url = $this->getPackageUrl($remote_manifest);

			if (!$package_url)
			{
				// Could not find the url , maybe the url is wrong in the update server, or there is not internet access
				$message = JText::sprintf('INSTL_DEFAULTLANGUAGE_COULD_NOT_INSTALL_LANGUAGE', $extension->name);
				$message .= ' ' . JText::_('INSTL_DEFAULTLANGUAGE_TRY_LATER');
				$app->enqueueMessage($message);
				continue;
			}

			// Download the package to the tmp folder
			$package = $this->downloadPackage($package_url);

			// Install the package
			if (!$installer->install($package['dir']))
			{
				// There was an error installing the package
				$message = JText::sprintf('INSTL_DEFAULTLANGUAGE_COULD_NOT_INSTALL_LANGUAGE', $extension->name);
				$message .= ' ' . JText::_('INSTL_DEFAULTLANGUAGE_TRY_LATER');
				$app->enqueueMessage($message);
				continue;
			}

			// Cleanup the install files in tmp folder
			if (!is_file($package['packagefile']))
			{
				$config = JFactory::getConfig();
				$package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
			}
			JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

			// Delete the installed language from the list
			$extension->delete($id);
		}

		return true;
	}

	/**
	 * Gets the manifest file of a selected language from a the language list in a update server.
	 *
	 * @param   integer  $uid  The id of the language in the #__updates table
	 *
	 * @return  string
	 *
	 * @since   3.0
	 */
	protected function getPackageManifest($uid)
	{
		$instance = JTable::getInstance('update');
		$instance->load($uid);
		$detailurl = trim($instance->detailsurl);

		return $detailurl;
	}

	/**
	 * Finds the url of the package to download.
	 *
	 * @param   string  $remote_manifest  url to the manifest XML file of the remote package
	 *
	 * @return  string|bool
	 *
	 * @since   3.0
	 */
	protected function getPackageUrl($remote_manifest)
	{
		$update = new JUpdate;
		$update->loadFromXML($remote_manifest);
		$package_url = trim($update->get('downloadurl', false)->_data);

		return $package_url;
	}

	/**
	 * Download a language package from a URL and unpack it in the tmp folder.
	 *
	 * @param   string  $url  url of the package
	 *
	 * @return  array|bool Package details or false on failure
	 *
	 * @since   3.0
	 */
	protected function downloadPackage($url)
	{
		// Download the package from the given URL
		$p_file = JInstallerHelper::downloadPackage($url);

		// Was the package downloaded?
		if (!$p_file)
		{
			JError::raiseWarning('', JText::_('COM_INSTALLER_MSG_INSTALL_INVALID_URL'));
			return false;
		}

		$config		= JFactory::getConfig();
		$tmp_dest	= $config->get('tmp_path');

		// Unpack the downloaded package file
		$package = JInstallerHelper::unpack($tmp_dest . '/' . $p_file);

		return $package;
	}

	/**
	 * Method to get installed extensions data.
	 *
	 * @return  object  The extension data
	 *
	 * @since   3.0
	 */
	protected function getPackageList()
	{
		// Create a new db object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select field element from the extensions table.
		$query->select('a.element, a.name');
		$query->from('#__extensions AS a');

		$query->where('a.type = ' . $db->quote('package'));
		$query->where('state = 0');
		$query->where('enabled = 1');

		$db->setQuery($query);

		$this->extlist = $db->loadColumn();

		return $this->extlist;
	}

	/**
	 * Get the current setup options from the session.
	 *
	 * @return  array
	 *
	 * @since   3.0
	 */
	public function getOptions()
	{
		$session = JFactory::getSession();
		$options = $session->get('setup.options', array());

		return $options;
	}
}
