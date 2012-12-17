<?php
/**
 * @package    Joomla.Installation
 *
 * @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Get version of Joomla! to compare it with the version of the language package
$ver = new JVersion;
?>
<script type="text/javascript">
	function installExtensions() {
		document.id(install_extensions_desc).hide();
		document.id(wait_installing).show();
		document.id(wait_installing_spinner).show();
		Install.submitform();
	}
</script>

<form action="index.php" method="post" id="adminForm" class="form-validate form-horizontal">
	<div class="alert alert-success">
		<h3>The base Joomla! Framework is successfully installed!</h3>
		<p>Now that you have the base framework, please select some packages to install.</p>
	</div>
	<div class="btn-toolbar">
		<div class="btn-group pull-right">
			<a
				class="btn btn-primary"
				href="#"
				onclick="installExtensions()"
				rel="next"
				title="<?php echo JText::_('JNEXT'); ?>">
				<i class="icon-arrow-right icon-white"></i>
				<?php echo JText::_('JNEXT'); ?>
			</a>
		</div>
	</div>
	<hr class="hr-condensed" />
	<?php if (!$this->items) : ?>
		<p>Joomla! was unable to load extension data at this time.  Please finish the installation process.</p>
		<p>
			<a href="#"
			class="btn btn-primary"
			onclick="return Install.goToPage('complete');">
			<i class="icon-arrow-left icon-white"></i>
			<?php echo JText::_('INSTL_LANGUAGES_WARNING_BACK_BUTTON'); ?>
			</a>
		</p>
		<p>You will be able to install additional extensions later through the Joomla! administrator.</p>
	<?php else : ?>
		<p id="install_extensions_desc">The Joomla! CMS is composed of numerous extensions.  They are separated here into packages.<br />Please select the packages you would like to use on your site.  You can uninstall these later if desired.</p>
		<p id="wait_installing" style="display: none;">
			This operation will take up to 10 seconds per package to complete<br />Please wait while we download and install the packages...<br />
			<div id="wait_installing_spinner" class="spinner spinner-img" style="display: none;"></div>
		</p>

	<table class="table table-striped table-condensed">
			<thead>
					<tr>
						<th>
							Package
						</th>
						<th>
							Version
						</th>
					</tr>
			</thead>
			<tbody>
				<?php foreach ($this->items as $i => $extension) : ?>
					<?php
					// Checks that the language package is valid for current Joomla version
					if (substr($extension->version, 0, 3) == $ver->RELEASE) :
					?>
					<tr>
						<td>
							<label class="checkbox">
								<input
									type="checkbox"
									id="cb<?php echo $i; ?>"
									name="cid[]"
									value="<?php echo $extension->update_id; ?>"
									/> <?php echo $extension->name; ?>
							</label>
						</td>
						<td>
							<span class="badge"><?php echo $extension->version; ?></span>
						</td>
					</tr>
					<?php endif; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
		<input type="hidden" name="task" value="extensions.installExtensions" />
		<?php echo JHtml::_('form.token'); ?>
	<?php endif; ?>
</form>
