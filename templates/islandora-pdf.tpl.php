<?php

/**
 * @file
 * This is the template file for the pdf object
 *
 * Overwriting default template to add in quick MODS support; to be improved...
 */

global $language;

$parts = parse_url(variable_get('islandora_solr_url', 'localhost:8080/solr'));
$solr = new Apache_Solr_Service($parts['host'], $parts['port'], $parts['path'] . '/');
$solr->setCreateDocuments(false);

try {
	$pid = $dc_array['dc:identifier']['value'];
	$solr_query = 'PID:"' . str_replace(':', '\:', $pid) . '"';
	$results = $solr->search($solr_query, 0, 1, array('fl' => 'mods_xml'));
}
catch (Exception $e) {
	// We don't need to display the error if we fall back to DC metadata?
	// drupal_set_message(check_plain(t('Error searching Solr index')) . ' ' . $e->getMessage(), 'error');
}

if (isset($results)) {
	$solr_results = json_decode($results->getRawResponse(), TRUE);

	if ($solr_results['response']->numFound == 0) {
		$modsXML = $solr_results['response']['docs'][0]['mods_xml'][0];
		$mods = simplexml_load_string($modsXML);
		$mods->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');
	}
}
?>

<div class="islandora-pdf-object islandora">
	<div class="islandora-pdf-content-wrapper clearfix">
		<?php if (isset($islandora_content)): ?>
		<div class="islandora-pdf-content">
			<?php print $islandora_content; ?>
		</div>
		<?php print $islandora_download_link; ?>
		<?php endif; ?>
		<div class="islandora-pdf-sidebar">
			<?php if (isset($mods)) { 
				// get MODS description from mods xml above
			} else { ?>
			<?php if (!empty($dc_array['dc:description']['value'])): ?>
			<h2><?php print $dc_array['dc:description']['label']; ?></h2>
			<p><?php print $dc_array['dc:description']['value']; ?></p>
			<?php endif; ?>
			<?php } ?>
			<?php if($parent_collections): ?>
			<div>
				<h2>
					<?php print t('In collections'); ?>
				</h2>
				<ul>
					<?php foreach ($parent_collections as $collection): ?>
					<li><?php print l($collection->label, "islandora/object/{$collection->id}"); ?>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>
		</div>
	</div>
	<fieldset class="collapsible collapsed islandora-pdf-metadata">
		<legend>
			<span class="fieldset-legend"><?php print t('Details'); ?> </span>
		</legend>
		<div class="fieldset-wrapper">
			<dl class="islandora-inline-metadata islandora-pdf-fields">
				<?php
				if (isset($mods)) {
					if ($language->name == 'Arabic') {
						$title = $mods->xpath('/mods/titleInfo[not(@type)]/title');
						echo '<dt class="mods-title first">العنوان</dt>';
						echo '<dd class="mods-title first"><span dir="ltr">' . $title[0] . '</span></dd>';
					} else {
						$title = $mods->xpath('/mods/titleInfo[@type="alternative"]/title');
						echo '<dt class="mods-title first">Title: </dt>';
						echo '<dd class="mods-title first">' . $title[0] . '</dd>';
					}
				} else {
				?>
				<?php $row_field = 0; ?>
				<?php foreach ($dc_array as $key => $value): ?>
				<dt class="<?php print $value['class']; ?><?php print $row_field == 0 ? ' first' : ''; ?>">
					<?php print $value['label']; ?>
				</dt>
				<dd class="<?php print $value['class']; ?><?php print $row_field == 0 ? ' first' : ''; ?>">
					<?php print $value['value']; ?>
				</dd>
				<?php $row_field++; ?>
				<?php endforeach; ?>
				<?php } ?>
			</dl>
		</div>
	</fieldset>
</div>
