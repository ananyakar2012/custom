<?php

namespace Drupal\actor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Link;
/**
 * Class actorlistController.
 */
class actorlistController extends ControllerBase {

  /**
   * Actorlist.
   *
   * @return string
   *   Return actor movie list.
   */
  public function actorlist() {
	$current_path = \Drupal::service('path.current')->getPath();
	$path_args = explode('/', $current_path);
	$actorDetails = Node::load($path_args[3]);
	$getActorList = self::getActorList($actorName,$movieName);
	$getFinalList = array();
	$countNum = 0;
	foreach($getActorList as $actorKey => $actorVal){
		if($path_args[3] == $actorVal['actorID']){
			$getFinalList += array($countNum => $actorVal);
			$countNum++;
		}
	}
	
	// Prepare _sortable_ table header
	$header = array(
		array('data' => t('Movie Name'), 'field' => 'movieName', 'sort' => 'asc'),
		array('data' => t('Movie Image'), 'field' => 'movieImage'),
		array('data' => t('Actor Desc'), 'field' => 'actorDesc'),
		array('data' => t('Co-Star Name'), 'field' => 'coStarName'),
	);
	$options = [
			'attributes' => [
			'class' => [
			  'use-ajax',
			],
			'data-dialog-type' => [
			  'modal',
			],
		],
	];
	$rows = $row = array();
	$limit = 10;
	if($_GET['page'] > 0)
		$page = $_GET['page'];	
	else
		$page = 0;
	
	for($i = $page*$limit; $i <= ($page+1)*$limit-1; $i++){
		foreach($getFinalList as $actorKey => $actorVal){
			if($i == $actorKey){
				if($actorVal['actorDesc'] != '')
					$actorDesc = t($actorVal['actorDesc']);
				if($actorVal['actorImage'] != '')
					$movieImage = t('<img src="'.$actorVal['movieImage'].'" height=100 width=100 />');
				$costarName = '';
				if(count($actorVal['coStarDetails']) > 0){
					foreach($actorVal['coStarDetails'] as $coStarVal){
						$costarName .= Link::fromTextAndUrl($coStarVal['coStarName'], Url::fromRoute('actor.actorlist_controller_coactorlist', ['cid' => $coStarVal['coStarID'],'mid' => $actorVal['movieID']], $options))->toString().",";
					}
					$costarName = t(substr($costarName,0,-1));
				}
				$row = array("movieName" => $actorVal['movieName'],"movieImage" => $movieImage,
				"actorDesc" => $actorDesc,"costarName" => $costarName);
				$rows[] = array('data' => $row);
			}
		}
	}
	
	
	pager_default_initialize(count($getFinalList), $limit);
    $build = array(
        '#markup' => t('Movies of '.$actorDetails->title->value)
    );
	$build['movies_table'] = array(
		'#theme' => 'table', '#header' => $header,
	    '#rows' => $rows,
		"#empty" => t("Table has no data!")
	);
	$build['pager'] = array(
       '#type' => 'pager'
     );
	return $build;
  }
  /**
   * Coactorlist.
   *
   * @return string
   *   Return co actor movie list.
   */
  public function coactorlist() {
	$current_path = \Drupal::service('path.current')->getPath();
	$path_args = explode('/', $current_path);
	$actorDetails = Node::load($path_args[3]);
	$getActorList = self::getActorList($actorName,$movieName);
	// Prepare _sortable_ table header
	$header = array(
		array('data' => t('Movie Name'), 'field' => 'movieName', 'sort' => 'asc'),
		array('data' => t('Actor Image'), 'field' => 'movieImage'),
		array('data' => t('Description'), 'field' => 'actorDesc'),
	);
	$rows = $row = array();
	
	foreach($getActorList as $actorKey => $actorVal){
	
		if($actorVal['movieID'] == $path_args[4]){
			$costarDesc = '';
			foreach($actorVal['coStarDetails'] as $coStarVal){
				if($coStarVal['coStarID'] == $path_args[3]){
					$costarDesc = $coStarVal['coStarDesc'];
				}
			}
			$row = array("movieName" => t($actorVal['movieName']),"movieImage" => t('<img src="'.$actorVal['actorImage'].'" height=100 width=100 />'),
			"actorDesc" => t($costarDesc));
			$rows[] = array('data' => $row);
		}
		
	}
	$build = array(
        '#markup' => t('Details of '.$actorDetails->title->value)
    );
	$build['movies_table'] = array(
		'#theme' => 'table', '#header' => $header,
	    '#rows' => $rows,
		"#empty" => t("Table has no data!")
	);
	return $build;
  }
  public function getActorList($actorSearchName='',$movieName=''){
	$query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'movies_list');
	if($movieName != '')
		$query->condition('title', '%'.$movieName.'%', 'LIKE');
	$query->sort('changed','DESC');
    $entity_ids = $query->execute();
	
	$actorArr = array();
	foreach($entity_ids as $ids){
		$actorID = Node::load($ids)->field_actor->target_id;
		$actorName = Node::load($actorID)->title->value;
		$count = 0;
		if($actorSearchName != ''){
			$count = 1;
			if(strstr($actorName, $actorSearchName) != ''){
				$count = 0;
			}
		}
		if($count == 0){
			$movieDetails = Node::load($ids);
			$actorDetails = Node::load($actorID);
			$actorImage = Node::load($actorID)->field_actor_image->entity->url();
			$movieName = $movieDetails->title->value;
			$movieDesc = $movieDetails->field_description->value;
			$actorDesc = $movieDetails->field_actor_description->value;
			$actorRating = $actorDetails->field_actor_rating->value;
			$userNumber = $actorDetails->field_user_number->value;
			$actorAvgRating = ceil($actorRating/$userNumber);
			if(isset($movieDetails->field_movie_image->entity))
				$movieImage = $movieDetails->field_movie_image->entity->url();
			//get data from paragraph co star
			$paragraph_field_items = $movieDetails->get('field_co_star_section')->getValue();
			$paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');
			$paramids = array_column($paragraph_field_items, 'target_id');
			$paragraphs_objects = $paragraph_storage->loadMultiple($paramids);

			$coStarArr = array();
			foreach ($paragraphs_objects as $paragraph) {
				$coStarID = $paragraph->get('field_co_star')->target_id;
				$coStarDetails = Node::load($coStarID);
				$coStarName = $coStarDetails->title->value;
				$coStarDesc = $paragraph->get('field_co_star_description')->value;
				$coStarArr []= array("coStarID" => $coStarID, "coStarName" =>$coStarName,"coStarDesc" =>$coStarDesc);
			}
			

			$coStarSectionDetails = Node::load(Node::load($ids)->field_co_star_section->target_id);
			$actorArr[] = array("movieID"  => $ids,"actorID" => $actorID, "actorName" => $actorName,"actorImage" => $actorImage,"movieName" => $movieName,"movieDesc" => $movieDesc,
			"coStarDetails" => $coStarArr,"movieImage" => $movieImage,"actorDesc" => $actorDesc,"actorAvgRating"=>$actorAvgRating);
		}
	}
	return $actorArr;
  }
}
