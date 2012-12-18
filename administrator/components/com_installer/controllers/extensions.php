<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_installer
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License, see LICENSE.php
 */

defined('_JEXEC') or die;

/**
 * Extensions Installer Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  com_installer
 * @since       2.5.7
 */
class InstallerControllerExtensions extends JControllerLegacy
{
	/**
	 * Finds new Extensions.
	 *
	 * @return  void
	 *
	 * @since   2.5.7
	 */
	public function find()
	{
		// Check for request forgeries
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Get the caching duration
		$component = JComponentHelper::getComponent('com_installer');
		$params = $component->params;
		$cache_timeout = $params->get('cachetimeout', 6, 'int');
		$cache_timeout = 3600 * $cache_timeout;

		// Find updates
		$model	= $this->getModel('extensions');
		$model->findExtensions($cache_timeout);

		$this->setRedirect(JRoute::_('index.php?option=com_installer&view=extensions', false));
	}

	/**
	 * Purgue the updates list.
	 *
	 * @return  void
	 *
	 * @since   2.5.7
	 */
	public function purge()
	{
		// Check for request forgeries
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Purge updates
		$model = $this->getModel('update');
		$model->purge();
		$model->enableSites();
		$this->setRedirect(JRoute::_('index.php?option=com_installer&view=extensions', false), $model->_message);
	}

	/**
	 * Install extensions.
	 *
	 * @return  void
	 *
	 * @since   2.5.7
	 */
	public function install()
	{
		$model = $this->getModel('extensions');

		// Get array of selected extensions
		$eids = $this->input->get('cid', array(), 'array');
		JArrayHelper::toInteger($eids, array());

		if (!$eids)
		{
			// No extensions have been selected
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('COM_INSTALLER_MSG_DISCOVER_NOEXTENSIONSELECTED'));
		}
		else
		{
			// Install selected extensions
			$model->install($eids);
		}

		$this->setRedirect(JRoute::_('index.php?option=com_installer&view=extensions', false));
	}
}
