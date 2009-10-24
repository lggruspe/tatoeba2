<?php
/*
    Tatoeba Project, free collaborativ creation of languages corpuses project
    Copyright (C) 2009 Etienne Deparis <etienne.deparis@umaneti.net>

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
echo $this->element('pmmenu');
?>
<div id="main_content">
	<div class="module">
		<h2><?php echo __($folder, true); ?></h2>
		<table>
		<?php
		if($folder == 'Sent')
			echo '<tr><th>'.__('Date', true).'</th><th>'.__('To', true).'</th><th>'.__('Subject', true).'</th></tr>';
		else
			echo '<tr><th>'.__('Date', true).'</th><th>'.__('From', true).'</th><th>'.__('Subject', true).'</th></tr>';
		foreach($content as $msg){
			echo '<tr><td>' . $html->link($date->ago($msg['date']), array('action' => 'show', $msg['id'])) . '</td>';
			echo '<td>'.$html->link($msg['from'], array('action' => 'create', $msg['from'])).'</td>';
			echo '<td>' . $html->link($msg['title'], array('action' => 'show', $msg['id'])) . '</td></tr>';
		} ?>
		</table>
	</div>
</div>
