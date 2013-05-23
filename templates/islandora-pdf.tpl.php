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

	if ($solr_results['response']['numFound'] > 0) {
		$modsXML = $solr_results['response']['docs'][0]['mods_xml'][0];
		$mods = simplexml_load_string($modsXML);
		
		if (is_object($mods)) {
			$mods->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');
		}
		else {
			$mods = NULL;
		}
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
					<li>
					<?php print l(t('@collectionName', array('@collectionName' =>
						$collection->label)), "islandora/object/{$collection->id}"); ?>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>
		</div>
	</div>
	<!-- removed 'collapsed' for a test -->
	<fieldset class="collapsible islandora-pdf-metadata">
		<legend>
			<span class="fieldset-legend"><?php print t('Extended details'); ?> </span>
		</legend>
		<div class="fieldset-wrapper">
			<dl class="islandora-inline-metadata islandora-pdf-fields">
				<?php
				if (isset($mods)) {
					echo '<dt class="mods-title first">' . t('Title') . ':</dt>';

					if ($language->name == 'Arabic') {
						$title = $mods->xpath('/mods/titleInfo[not(@type)]/title');
						echo '<dd class="mods-title first"><span dir="ltr">' . $title[0] . '</span></dd>';
						drupal_set_title($title[0]);
					} else {
						$title = $mods->xpath('/mods/titleInfo[@type="alternative"]/title');
						echo '<dd class="mods-title first">' . $title[0] . '</dd>';
						drupal_set_title($title[0]);
					}
					
					$altTitle = $mods->xpath('/mods/titleInfo[@type="alternative"]/title');
					if (count($altTitle) > 1) {
						echo '<dt class="mods-title">' . t('Alternative title') . ':</dt>';
						echo '<dd class="mods-title"><span dir="ltr">' . $altTitle[1] . '</span></dd>';
					}
					
					$dateCreated = $mods->xpath('/mods/originInfo/dateCreated[not(@encoding)]');
					if (count($dateCreated) > 1) {
						echo '<dt class="mods-dateCreated">' . t('Date created') . ':</dt>';
						echo '<dd class="mods-dateCreated">' . $dateCreated[0] . '</dd>';
					}

					// TODO: wrap these repeated code blocks in a nice function
					
					$genres = $mods->xpath('/mods/genre');
					$index = 0;
					$total = count($genres);
					foreach ($genres as $genre) {
						if ($index == 0) {
							echo '<dt class="mods-genre">';
							if ($total > 1) echo t('Genres'); else echo t('Genre');
							echo ':</dt><dd class="mods-genre">';
						}
					
						echo '<span dir="ltr">' . $genre . '</span><br/>';
						$index++;
					
						if ($index == $total) {
							echo '</dd>';
						}
					}
					
					if ($language->name == "Arabic") {
						$topics = $mods->xpath('/mods/subject[@authority="local"]/topic');
						$index = 0;
						$total = count($topics);
						foreach ($topics as $topic) {
							if ($index == 0) {
								echo '<dt class="mods-topic">';
								if ($total > 1) echo t('Topics'); else echo t('Topic');
								echo ':</dt><dd class="mods-topic">';
							}
							
							echo '<span dir="ltr">' . $topic . '</span><br/>';
							$index++;
							
							if ($index == $total) {
								echo '</dd>';
							}
						}
					}
					
					$topics = $mods->xpath('/mods/subject[not(@authority="local")]/topic');
					$index = 0;
					$total = count($topics);
					foreach ($topics as $topic) {
						if ($index == 0) {
							echo '<dt class="mods-topic">';
							if ($total > 1) echo t('Topics'); else echo t('Topic');
							echo ':</dt><dd class="mods-topic">';
						}
							
						echo '<span dir="ltr">' . $topic . '</span><br/>';
						$index++;
							
						if ($index == $total) {
							echo '</dd>';
						}
					}
					
					$geographics = $mods->xpath('/mods/subject/geographic');
					$index = 0;
					$total = count($geographics);
					foreach ($geographics as $geographic) {
						if ($index == 0) {
							echo '<dt class="mods-geographic">';
							if ($total > 1) echo t('Geographics'); else echo t('Geographic');
							echo ':</dt><dd class="mods-geographic">';
						}
							
						echo '<span dir="ltr">' . $geographic . '</span><br/>';
						$index++;
							
						if ($index == $total) {
							echo '</dd>';
						}
					}
					
					$names = $mods->xpath('/mods/name/namePart');
					$index = 0;
					$total = count($names);
					foreach ($names as $name) {
						if ($index == 0) {
							echo '<dt class="mods-name">';
							if ($total > 1) echo t('Subjects'); else echo t('Subject');
							echo ':</dt><dd class="mods-name">';
						}

						echo '<span dir="ltr">' . $name . '</span><br/>';
						$index++;

						if ($index == $total) {
							echo '</dd>';
						}
					}
					
					$notes = $mods->xpath('/mods/note');
					$index = 0;
					$total = count($notes);
					foreach ($notes as $note) {
						if ($index == 0) {
							echo '<dt class="mods-note">';
							if ($total > 1) echo t('Notes'); else echo t('Note');
							echo ':</dt><dd class="mods-note">';
						}

						echo '<span dir="ltr">' . $note . '</span><br/>';						
						$index++;
						
						if ($index == $total) {
							echo '</dd>';
						}
					}
					
					$lang = $mods->xpath('/mods/language/languageTerm[@type="code"]');
					if ($lang == 'ara') {
						echo '<dt class="mods-language">' . t('Language') . ':</dt>';
						echo '<dd class="mods-language">' . t('Arabic') . '</dd>';
					}
					if ($lang == 'eng') {
						echo '<dt class="mods-language">' . t('Language') . ':</dt>';
						echo '<dd class="mods-language">' . t('English') . '</dd>';
					}
					
					$extent = $mods->physicalDescription->extent;
					if (isset($extent)) {
						echo '<dt class="mods-extent">' . t('Description') . ':</dt>';
						echo '<dd class="mods-extent"><span dir="ltr">' . $extent . '</span></dd>';
					}
					
					$recordId = $mods->recordInfo->recordIdentifier;
					echo '<dt class="mods-recordID">' . t('Record ID') . ':</dt>';
					echo '<dd class="mods-recordID">' . $recordId . '</dd>';
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
