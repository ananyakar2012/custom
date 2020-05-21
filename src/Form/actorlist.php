<?php

namespace Drupal\actor\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\Entity\Node;
use Drupal\actor\Controller\actorlistController;
/**
 * Class actorlist.
 */
class actorlist extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'actorlist';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	$actorName = $form_state->getValue('actor_name');
	$movieName = $form_state->getValue('movie_name');
	
	$obj = new actorlistController;
	$getActorList = $obj::getActorList($actorName,$movieName);
    $form['actor_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Actor Name'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['movie_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Movie Name'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      // The AJAX handler will call our callback, and will replace whatever page
      // element has id box-container.
      '#ajax' => [
        'callback' => '::promptCallback',
        'wrapper' => 'container-div',
      ],
      '#value' => $this->t('Submit'),
    ];
    // This container wil be replaced by AJAX.
	$form['container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'box-container'],
	  '#prefix' => '<div id="container-div"><table><tr><th>Actor Name</th><th>Actor Image</th><th>Movie Name</th><th>movie Desc</th><th>Rating</th></tr>',
	  '#suffix' => '</table></div>',
    ];
	$actorIDArr = array();
	foreach($getActorList as $actorKey => $actorVal){
		if(!in_array($actorVal['actorID'], $actorIDArr)){
			
			$form['container']["actorID_".$actorVal['actorID']] = [
			  '#type' => 'select',
			  // The AJAX handler will call our callback, and will replace whatever page
			  // element has id box-container.
			  '#ajax' => [
				'callback' => '::ratingCallback'
			  ],
			  '#prefix' => '<tr><td><a href="/actor/actorlist/'.$actorVal['actorID'].'">'.$actorVal['actorName'].'</a></td>
							<td><img src="'.$actorVal['actorImage'].'" height=100 width=100 /></td>
							<td>'.$actorVal['movieName'].'</td>
							<td>'.$actorVal['movieDesc'].'</td><td>',
			  '#options' => array(0=>"Select",1=>"1 of 5",2=>"2 of 5",3=>"3 of 5",4=>"4 of 5", 5=>"5 of 5"),
			  '#default_value' => $actorVal['actorAvgRating'],
			  '#suffix' => '</td></tr>'
			];
			$actorIDArr += array($actorKey=>$actorVal['actorID']);
		}
	}
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	  $form_state->setRebuild(TRUE);
  }
  
   public function promptCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(true);
    return $form['container'];
  }
  public function ratingCallback(array &$form, FormStateInterface $form_state) {
    // In most cases, it is recommended that you put this logic in form
    // generation rather than the callback. Submit driven forms are an
    // exception, because you may not want to return the form at all.
	$triggeredElementExp = explode("-",$form_state->getTriggeringElement()['#id']);
	$actorRating = $form_state->getValue("actorID_".$triggeredElementExp[2]);
	
	$node = Node::load($triggeredElementExp[2]);
	$actor_rating = $user_number = 0;
	if($node->field_actor_rating->value > 0)
		$actor_rating = $node->field_actor_rating->value;
	if($node->field_user_number->value > 0)
		$user_number = $node->field_user_number->value;
	//set value for field
	$node->field_actor_rating->value = $actor_rating+$actorRating;
	$node->field_user_number->value = $user_number+1;
	
	//save to update node
	$node->save();
  }
}
