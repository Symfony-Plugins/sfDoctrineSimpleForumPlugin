  <?php use_helper("Date")?>
  <h3 class="toolbar">Welcome to the forum!</h3>
  <div style="float: right"><?php echo link_to("View Latest Feed " . image_tag("/sfDoctrineSimpleForumPlugin/images/feed-icon.png", array("style"=>"border: 0;")), "sf_doctrine_simple_forum_view_latest_feed", array("class"=>"feed"))?></div><br /><br />
  <table id="thread_table">
  	<thead>
    <tr>
      <th class="forum_name"><?php echo __('Forum', null, 'sfDoctrineSimpleForum') ?></th>
      <th class="forum_threads"><?php echo __('Topics', null, 'sfDoctrineSimpleForum') ?></th>
      <th class="forum_posts"><?php echo __('Messages', null, 'sfDoctrineSimpleForum') ?></th>
      <th class="forum_recent"><?php echo __('Last Post', null, 'sfDoctrineSimpleForum') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($forums as $forum): ?>
        <tr class="category">
          <td class="category_header" colspan="4"><?=$forum->getCategories()->getName() ?></td>
        </tr>        
      <?php include_partial('sfDoctrineSimpleForum/forum', array('forum' => $forum)) ?>
    <?php endforeach; ?>
    </tbody>
  </table>