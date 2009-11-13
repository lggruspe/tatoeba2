<?php
if($is_public or $login){

	echo $html->css('tatoeba.profile', false);
?>
<div id="main_content">
	<div class="module">
		<h3><?php echo $user['User']['username'] ?></h3>
		<div id="pimg">
<?php
echo $html->image('profiles/' . (empty($user['User']['image']) ? 'tatoeba_user.png' : $user['User']['image'] ), array(
	'alt' => $user['User']['username'],
));
?>
		</div>
	</div>
<?php
if(!empty($user['User']['description'])){
?>
	<div id="pdescription" class="module">
		<h3><?php __('Something about you') ?></h3>
		<div id="profile_description"><?php echo $user['User']['description'] ?></div>
	</div>
<?php
}
?>
	<div id="pbasic" class="module">
		<h3><?php __('Basic Information') ?></h3>
		<dl>
<?php
if(!empty($user['User']['name'])){
?>
			<dt>Name</dt>
			<dd><?php echo $user['User']['name'] ?></dd>
<?php
}

$sBirthday = (empty($user['User']['birthday']) or $user['User']['birthday'] == 'DD/MM/YYYY') ? 'DD/MM/YYYY' : date('d/m/Y', strtotime($user['User']['birthday']));

if($sBirthday != 'DD/MM/YYYY'){
?>
			<dt>Birthday</dt>
			<dd><?php echo $sBirthday ?></dd>
<?php
}

if(is_string($user['User']['country_id']) and strlen($user['User']['country_id']) == 2){
?>
			<dt>Country</dt>
			<dd><?php echo $user['Country']['name'] ?></dd>
<?php
}
?>
		</dl>
	</div>

	<div class="module">
		<h3>Activity informations</h3>
		<dl>
			<dt>Joined</dt>
			<dd><?php echo date('r', strtotime($user['User']['since'])) ?></dd>
			<dt>Last login</dt>
			<dd><?php echo date('r', $user['User']['last_time_active']) ?></dd>
			<dt>Comment posted</dt>
			<dd><?php echo count($user['SentenceComments']) ?></dd>
			<dt>Sentences owned</dt>
			<dd><?php echo count($user['Sentences']) ?></dd>
			<dt>Sentences favorited</dt>
			<dd><?php echo count($user['Favorite']) ?></dd>
		</dl>
	</div>

	<div id="pcontact" class="module">
		<h3><?php __('Contact informations') ?></h3>
		<dl>
			<dt>E-mail</dt>
			<dd><?php echo $user['User']['email'] ?></dd>
<?php
if(!empty($user['User']['homepage'])){
?>
			<dt>Homepage</dt>
			<dd><?php echo '<a href="' . $user['User']['homepage'] . '" title="' . $user['User']['username'] . '">' . $user['User']['homepage'] . '</a>' ?></dd>
<?php
}
?>
		</dl>
	</div>

</div>

<?php
}else{
?>
This profile is protected. You must login to see it.
<?php
}
?>