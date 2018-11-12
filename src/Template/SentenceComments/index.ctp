<?php
/**
 * Tatoeba Project, free collaborative creation of multilingual corpuses project
 * Copyright (C) 2009  HO Ngoc Phuong Trang <tranglich@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  Tatoeba
 * @author   HO Ngoc Phuong Trang <tranglich@gmail.com>
 * @license  Affero General Public License
 * @link     http://tatoeba.org
 */
use App\Model\CurrentUser;

/**
 * Display latest comments.
 *
 * @category SentenceComments
 * @package  Views
 * @author   HO Ngoc Phuong Trang <tranglich@gmail.com>
 * @license  Affero General Public License
 * @link     http://tatoeba.org
 */

$this->set('title_for_layout', $this->Pages->formatTitle(__('Comments on sentences')));

$this->Paginator->options(
    array(
        'url' => $this->request->params['pass']
    )
);
?>

<div id="annexe_content">
    <?php $this->CommonModules->createFilterByLangMod(); ?>
</div>

<div id="main_content">
    <div class="section">
        <h2>
            <?php
            echo $this->Paginator->counter(
                array(
                    'format' => __(
                        'Comments on sentences (total %count%)'
                    )
                )
            );
            ?>
        </h2>

        <?php
        $paginationUrl = array($langFilter);
        $this->Pagination->display($paginationUrl);
        $currentUserIsMember = CurrentUser::isMember();

        foreach ($sentenceComments as $i => $comment) {
            $menu = $this->Comments->getMenuForComment(
                $comment['SentenceComment'],
                $commentsPermissions[$i],
                $currentUserIsMember
            );

            echo $this->element(
                'messages/comment',
                array(
                    'comment' => $comment,
                    'menu' => $menu,
                    'replyIcon' => $currentUserIsMember
                )
            );
        }

        $this->Pagination->display($paginationUrl);
        ?>

    </div>
</div>
