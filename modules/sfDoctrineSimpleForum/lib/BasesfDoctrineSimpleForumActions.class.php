<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2007 Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) 2009 Jamie Hall <jstevenhall@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage plugin
 * @author     Jamie Hall <jstevenhall@gmail.com>         
 * @version    SVN: $Id$
 */

class BasesfDoctrineSimpleForumActions extends sfActions
{

  /* Common methods */

  private function isModerator()
  {
    if($this->getUser()->hasGroup("sf_doctrine_simple_forum_moderator_group"))
    {
        return true;
    }
    else
    {
        return false;
    }
  }

  private function isTopicOwner($topic)
  {
    if($topic->getUserId() == $this->getUser()->getGuardUser()->getId())
    {
        return true;
    }
    else
    {
        return false;
    }
  }


  public function executeIndex(sfWebRequest $request)
  {
    $this->forums = Doctrine::getTable("sfDoctrineSimpleForumForum")->findAll();
  }
  public function executeViewBoard(sfWebRequest $request)
  {
	// get un-stickied
  	$topic = new sfDoctrineSimpleForumTopic;
        $this->topics = Doctrine::getTable("sfDoctrineSimpleForumTopic")->findAll();
  	$this->stickies = $topic->getSticked($request->getParameter("id"));
  	$this->board = $topic->getUnsticked($request->getParameter("id"));  	
  	// get stickied
  	$this->forum = Doctrine::getTable("sfDoctrineSimpleForumForum")->findOneById($request->getParameter("id"));	
  
  	// forward to 404 page if id is invalid
  	//$this->forward404Unless($this->board);
  }
  
  public function executeViewTopic(sfWebRequest $request)
  {
  	$topic = Doctrine::getTable("sfDoctrineSimpleForumTopic")->findOneById($request->getParameter("id"));
  	// increment views
  	$views = $topic->getNbViews() + 1;
  	$topic->setNbViews($views);
  	$topic->save();
  	
  	$this->forward404Unless($topic);
  	$this->topic = $topic;
        // check for reply
  	if($request->isMethod("post"))
  	{
  		// check user is authenticated
  		if($this->getUser()->isAuthenticated())
  		{
			// create new record
			$post = new sfDoctrineSimpleForumPost;
  			$post->setTopicId($this->topic->getId());
			$post->setUserId($this->getUser()->getGuardUser()->getId());
			$post->setForumId($this->topic->getForumId());
			if($request->hasParameter("thread_reply"))
			{
				$post->setContent($request->getParameter("thread_reply"));
			}
			// reply to another comment
			if($request->hasParameter("comment_post_id"))
			{
				$post->setContent($request->getParameter("comment"));
				$post->setIsReplyToComment(1);
				$post->setReplyId($request->getParameter("comment_post_id"));				
			}
							
			$post->save();	
			// set flash
			$this->getUser()->setFlash("success", $this->getContext()->getI18N()->__("Thank you for submitting your post!", null, 'sfDoctrineSimpleForum'));
			// redirect to clear post
			$this->redirect(sprintf("@sf_doctrine_simple_forum_view_topic?id=%s&slug=%s", $this->topic->getId(), $this->topic->getSlug())); 		
		}
  	}
  	  	
  }
  
  public function executeCreateTopic(sfWebRequest $request)
  {
    $this->forum = Doctrine::getTable("sfDoctrineSimpleForumForum")->findOneById($request->getParameter("id"));
    $this->forward404Unless($this->forum);

    $this->form = new sfDoctrineSimpleForumCreateTopicForm;
    if ($request->isMethod('post'))
    {
      $this->form->bind($request->getParameter('sf_doctrine_simple_forum_topic_create'));
      if ($this->form->isValid())
      {
	 // form is valid. insert records
            $topic = new sfDoctrineSimpleForumTopic;
            $values = $this->form->getValues();
            // set title
            $topic->setTitle($values['title']);
            // set user id
            $topic->setUserId($this->getUser()->getGuardUser()->getId());
            // set forum id
            $topic->setForumId($this->forum->getId());
            $topic->save();

            $post = new sfDoctrineSimpleForumPost;
            $post->setContent($values['content']);
            $post->setUserId($this->getUser()->getGuardUser()->getId());
            $post->setForumId($this->forum->getId());
            $post->setTopicId($topic->getId());
            $post->save();

            // redirect to topic
            $this->redirect(sprintf("@sf_doctrine_simple_forum_view_topic?id=%s&slug=%s", $topic->getId(), $topic->getSlug()));
      }
    }
  }
  
  public function executeViewLatestFeed()
  {
  	$this->latest_posts = Doctrine_Query::create()->from("sfDoctrineSimpleForumPost p")->orderBy("p.created_at DESC")->execute();	
  }

  /* Lock a thread */
  public function executeLockTopic(sfWebRequest $request)
  {
      $topic = Doctrine::getTable("sfDoctrineSimpleForumTopic")->findOneById($request->getParameter("id"));
      $this->forward404Unless($topic);
      if(self::isTopicOwner($topic) || self::isModerator())
      {
        $topic->lock(1);
        $this->redirect(sprintf("@sf_doctrine_simple_forum_view_topic?id=%s&slug=%s", $topic->getId(), $topic->getSlug()));
      }
      else
      {
	$this->getUser()->setFlash("error", $this->getContext()->getI18N()->__("You do not have permission to perform that action", null, 'sfDoctrineSimpleForum'));
        $this->redirect(sprintf("@sf_doctrine_simple_forum_view_topic?id=%s&slug=%s", $topic->getId(), $topic->getSlug()));
      };
  }

  /* Edit Topic */
  public function executeEditTopic(sfWebRequest $request)
  {
    $topic = Doctrine::getTable("sfDoctrineSimpleForumTopic")->findOneById($request->getParameter("id"));
    $this->forum = Doctrine::getTable("sfDoctrineSimpleForumForum")->findOneById($topic->getForumId());
    $this->form = new sfDoctrineSimpleForumCreateTopicForm($topic, array(), false);
    if ($request->isMethod('post'))
    {
      $this->form->bind($request->getParameter('sf_doctrine_simple_forum_topic_create'));
      if ($this->form->isValid())
      {
	 // form is valid. insert records
            $topic = new sfDoctrineSimpleForumTopic;
            $values = $this->form->getValues();
            // set title
            $topic->setTitle($values['title']);
            // set user id
            $topic->setUserId($this->getUser()->getGuardUser()->getId());
            // set forum id
            $topic->setForumId($this->forum->getId());
            $topic->save();

            $post = new sfDoctrineSimpleForumPost;
            $post->setContent($values['content']);
            $post->setUserId($this->getUser()->getGuardUser()->getId());
            $post->setForumId($this->forum->getId());
            $post->setTopicId($topic->getId());
            $post->save();

            // redirect to topic
            $this->redirect(sprintf("@sf_doctrine_simple_forum_view_topic?id=%s&slug=%s", $topic->getId(), $topic->getSlug()));
      }
    }
  }
  /* Unlock a topic */
  public function executeUnlockTopic(sfWebRequest $request)
  {
      $topic = Doctrine::getTable("sfDoctrineSimpleForumTopic")->findOneById($request->getParameter("id"));
      $this->forward404Unless($topic);
      if(self::isTopicOwner($topic) || self::isModerator())
      {
        $topic->lock(0);
        $this->redirect(sprintf("@sf_doctrine_simple_forum_view_topic?id=%s&slug=%s", $topic->getId(), $topic->getSlug()));
      }
      else
      {
	$this->getUser()->setFlash("error", $this->getContext()->getI18N()->__("You do not have permission to perform that action", null, 'sfDoctrineSimpleForum'));
        $this->redirect(sprintf("@sf_doctrine_simple_forum_view_topic?id=%s&slug=%s", $topic->getId(), $topic->getSlug()));
      }
  }
  /* Delete a topic */
  public function executeDeleteTopic(sfWebRequest $request)
  {
      $topic = Doctrine::getTable("sfDoctrineSimpleForumTopic")->findOneById($request->getParameter("id"));
      $this->forward404Unless($topic);
      if(self::isTopicOwner($topic) || self::isModerator())
      {
        $topic->deleteTopic();
        $this->redirect(sprintf("@sf_doctrine_simple_forum_view_board?id=%s&slug=%s",$topic->getForumId(),$topic->getForum()->getSlug()));
      }
      else
      {
	$this->getUser()->setFlash("error", $this->getContext()->getI18N()->__("You do not have permission to perform that action", null, 'sfDoctrineSimpleForum'));
        $this->redirect(sprintf("@sf_doctrine_simple_forum_view_topic?id=%s&slug=%s", $topic->getId(), $topic->getSlug()));
      }
  }

  /* Sticky a topic */
  public function executeStickyTopic(sfWebRequest $request)
  {
      $topic = Doctrine::getTable("sfDoctrineSimpleForumTopic")->findOneById($request->getParameter("id"));
      $this->forward404Unless($topic);
      if(self::isTopicOwner($topic) || self::isModerator())
      {
        $topic->sticky(1);
        $this->getUser()->setFlash("success", $this->getContext()->getI18N()->__("This thread has now been sticked", null, 'sfDoctrineSimpleForum'));
        $this->redirect(sprintf("@sf_doctrine_simple_forum_view_topic?id=%s&slug=%s", $topic->getId(), $topic->getSlug()));
      }
      else
      {
	$this->getUser()->setFlash("error", $this->getContext()->getI18N()->__("You do not have permission to perform that action", null, 'sfDoctrineSimpleForum'));
        $this->redirect(sprintf("@sf_doctrine_simple_forum_view_topic?id=%s&slug=%s", $topic->getId(), $topic->getSlug()));
      };
  }

  public function executeUnstickyTopic(sfWebRequest $request)
  {
      $topic = Doctrine::getTable("sfDoctrineSimpleForumTopic")->findOneById($request->getParameter("id"));
      $this->forward404Unless($topic);
      if(self::isTopicOwner($topic) || self::isModerator())
      {
        $topic->sticky(0);
        $this->getUser()->setFlash("success", $this->getContext()->getI18N()->__("This thread has now been unsticked", null, 'sfDoctrineSimpleForum'));
        $this->redirect(sprintf("@sf_doctrine_simple_forum_view_topic?id=%s&slug=%s", $topic->getId(), $topic->getSlug()));
      }
      else
      {
	$this->getUser()->setFlash("error", $this->getContext()->getI18N()->__("You do not have permission to perform that action", null, 'sfDoctrineSimpleForum'));
        $this->redirect(sprintf("@sf_doctrine_simple_forum_view_topic?id=%s&slug=%s", $topic->getId(), $topic->getSlug()));
      };
  }
}
  