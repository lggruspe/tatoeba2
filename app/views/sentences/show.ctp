<?php
/*
    Tatoeba Project, free collaborativ creation of languages corpuses project
    Copyright (C) 2009  TATOEBA Project(should be changed)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

?>

<div id="annexe_content">
	<div class="module">
		<?php
			if(!$session->read('Auth.User.id')){
				echo $this->element('login'); 
			} else {
				echo $this->element('space'); 
			}
		?>
	</div>
	<div class="module">
		<?php
		echo '<h2>';
		__('Comments');
		echo ' ';
		$tooltip->display(__('If you see any mistake, don\'t hesitate to post a comment about it!',true));
		echo '</h2>';
		
		echo '<a name="comments"></a>';
		echo '<div class="comments">';
		if(count($sentence['SentenceComment']) > 0){
			foreach($sentence['SentenceComment'] as $comment){
				$comments->displayComment(
					$comment['User']['id'],
					$comment['User']['username'], 
					$comment['created'], 
					$comment['text']
				);
			}
		}else{
			echo '<em>' . __('There are no comments for now.', true) .'</em>';	
		}
		echo '</div>';
		?>
		<p class="more_link">
			<?=$html->link(
				__('See all comments',true),
				array(
					"controller" => "sentence_comments",
					"action" => "show",
					$sentence['Sentence']['id']
				)); ?>
		</p>
	</div>

</div>

<div id="main_content">
	<div class="module">
		<?php
		if($sentence != null){
			$this->pageTitle = __('Example sentence : ',true) . $sentence['Sentence']['text'];
		
			// navigation (previous, random, next)
			$navigation->displaySentenceNavigation();
			
			echo '<div class="sentences_set">';
				// sentence menu (translate, edit, comment, etc)
				$specialOptions['belongsTo'] = $sentence['User']['username']; // TODO set up a better mechanism
				$sentences->displayMenu($sentence['Sentence']['id'], $sentence['Sentence']['lang'], $specialOptions);
		
				// sentence and translations
				$t = (isset($sentence['Translation'])) ? $sentence['Translation'] : array();
				$sentence['User']['canEdit'] = $specialOptions['canEdit']; // TODO set up a better mechanism
				$sentences->displayGroup($sentence['Sentence'], $t, $sentence['User']);
			echo '</div>';
			
			//$tooltip->displayAdoptTooltip(); 
			echo '<script type="text/javascript">
			$(document).ready(function(){
				$(".translations").html("<div class=\"loading\">'.addslashes($html->image('loading.gif')).'</div>");
				$(".translations").load("http://" + self.location.hostname + "/sentences/get_translations/'.$sentence['Sentence']['id'].'");
			});
			</script>';
			?>
			<p class="more_link translateLink"><a><?=__('Add a translation',true); ?></a></p>
			<?
			
		}else{
			$this->pageTitle = __('Sentence does not exist : ', true) . $this->params['pass'][0];
			
			// navigation (previous, random, next)
			$navigation->displaySentenceNavigation('random');
			
			echo '<div class="error">';
			__('There is no sentence with id ');
			echo $this->params['pass'][0];
			echo '</div>';
		}
		?>
	</div>
	<div class="module">
		<?php
		echo '<h2>';
		__('Logs'); 
		echo ' ';
		$tooltip->displayLogsColors();
		echo '</h2>';
		$contributions = $sentence['Contribution'];
		if(count($contributions) > 0){
			echo '<table id="logs">';
			foreach($contributions as $contribution){
				$logs->entry($contribution, $contribution['User']);
			}
			echo '</table>';
		}else{
			echo '<em>'. __('There is no log for this sentence', true) .'</em>';
		}
		?>
		<p class="more_link"><?= $html->link(
			__('See all logs',true),
			array(
				"controller" => "contributions",
				"action" => "show",
				$sentence['Sentence']['id']
			));?>
		</p>
	</div>
</div>

