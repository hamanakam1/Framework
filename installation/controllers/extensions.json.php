<?php
/**
 * @package    Joomla.Installation
 *
 * @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JLoader::register('InstallationControllerSetup', __DIR__ . '/setup.json.php');

/**
 * Setup controller for the Joomla Core Installer Extensions feature.
 * - JSON Protocol -
 *
 * @package  Joomla.Installation
 * @since    3.0
 */
class InstallationControllerExtensions extends InstallationControllerSetup
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   3.0
	 */
	public function __construct($config = array())
	{
		// Overrides application config and set the configuration.php file so tokens and database works
		JFactory::$config = null;
		JFactory::getConfig(JPATH_SITE . '/configuration.php');
		JFactory::$session = null;
		parent::__construct();
	}

	/**
	 * Method to install extensions to Joomla application.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function installExtensions()
	{
		// Check for a valid token. If invalid, send a 403 with the error message.
		JSession::checkToken() or $this->sendResponse(new Exception(JText::_('JINVALID_TOKEN'), 403));

		// Get the application object.
		$app = JFactory::getApplication();

		// Get array of selected languages
		$lids = $this->input->get('cid', array(), 'array');
		JArrayHelper::toInteger($lids, array());

		// Get the languages model.
		$model = $this->getModel('Extensions', 'InstallationModel');

		$return = false;

		if (!$lids)
		{
			// No languages have been selected
			$app->enqueueMessage(JText::_('INSTL_LANGUAGES_NO_LANGUAGE_SELECTED'));
		}
		else
		{
			// Install selected languages
			$return = $model->install($lids);
		}

		$r = new stdClass;

		// Check for validation errors.
		if ($return === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof Exception)
				{
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				}
				else
				{
					$app->enqueueMessage($errors[$i], 'warning');
				}
			}

			// Redirect back to the language selection screen.
			$r->view = 'extensions';
			$this->sendResponse($r);
		}

		// Create a response body.
		$r->view = 'complete';

		// Send the response.
		$this->sendResponse($r);
	}
}
