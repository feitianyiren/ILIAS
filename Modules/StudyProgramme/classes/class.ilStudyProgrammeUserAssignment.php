<?php

/* Copyright (c) 2015 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

require_once("./Modules/StudyProgramme/classes/model/class.ilStudyProgrammeAssignment.php");

/**
 * Represents one assignment of a user to a study programme.
 *
 * A user could have multiple assignments per programme.
 *
 * @author : Richard Klees <richard.klees@concepts-and-training.de>
 */
class ilStudyProgrammeUserAssignment {
	protected $assignment; // ilStudyProgrammeAssignment
	
	/**
	 * Throws when id does not refer to a study programme assignment.
	 *
	 * @throws ilException
	 * @param int | ilStudyProgrammeAssignment $a_id_or_model
	 */
	public function __construct($a_id_or_model) {
		if ($a_id_or_model instanceof ilStudyProgrammeAssignment) {
			$this->assignment = $a_id_or_model;
		}
		else {
			$this->assignment = ilStudyProgrammeAssignment::find($a_id_or_model);
		}
		if ($this->assignment === null) {
			throw new ilException("ilStudyProgrammeUserAssignment::__construct: "
								 ."Unknown assignmemt id '$a_id_or_model'.");
		}
	}
	
	/**
	 * Get an instance. Just wraps constructor.
	 *
	 * @throws ilException
	 * @param  int $a_id
	 * @return ilStudyProgrammeUserAssignment
	 */
	static public function getInstance($a_id) {
		return new ilStudyProgrammeUserAssignment($a_id);
	}
	
	/**
	 * Get all instances for a given user.
	 *
	 * @param int $a_user_id
	 * @return ilStudyProgrammeUserAssignment[]
	 */
	static public function getInstancesOfUser($a_user_id) {
		$assignments = ilStudyProgrammeAssignment::where(array( "usr_id" => $a_user_id ))
													->get();
		return array_map(function($ass) {
			return new ilStudyProgrammeUserAssignment($ass);
		}, array_values($assignments)); // use array values since we want keys 0...
	}
	
	/**
	 * Get all assignments that were made to the given program.
	 *
	 * @param int $a_program_id
	 * @return ilStudyProgrammeUserAssignment[]
	 */
	static public function getInstancesForProgram($a_program_id) {
		$assignments = ilStudyProgrammeAssignment::where(array( "root_prg_id" => $a_program_id ))
													->get();
		return array_map(function($ass) {
			return new ilStudyProgrammeUserAssignment($ass);
		}, array_values($assignments)); // use array values since we want keys 0...
	}
	
	/**
	 * Get the id of the assignment.
	 *
	 * @return int
	 */
	public function getId() {
		return $this->assignment->getId();
	}
	
	/**
	 * Get the program node where this assignment was made. 
	 *
	 * Throws when program this assignment is about has no ref id.
	 *
	 * @throws ilException
	 * @return ilObjStudyProgramme
	 */
	public function getStudyProgramme() {
		require_once("./Modules/StudyProgramme/classes/class.ilObjStudyProgramme.php");
		$refs = ilObject::_getAllReferences($this->assignment->getRootId());
		if (!count($refs)) {
			throw new ilException("ilStudyProgrammeUserAssignment::getStudyProgramme: "
								 ."could not find ref_id for program '"
								 .$this->assignment->getRootId()."'.");
		}
		return ilObjStudyProgramme::getInstanceByRefId(array_shift($refs));
	}
	
	/**
	 * Get the id of the user who is assigned.
	 *
	 * @return int
	 */
	public function getUserId() {
		return $this->assignment->getUserId();
	}
	
	/**
	 * Remove this assignment.
	 */
	public function remove() {
		$this->getStudyProgramme()->removeAssignment($this);
	}
	
	/**
	 * Delete the assignment from database.
	 */
	public function delete() {
		$this->assignment->delete();
	}
	
	/**
	 * Update all unmodified nodes in this assignment to the current state
	 * of the program.
	 *
	 * @return $this
	 */
	public function updateFromProgram() {
		$prg = $this->getStudyProgramme();
		$id = $this->getId();

		$prg->applyToSubTreeNodes(function($node) use ($id) {
			/**
			 * @var ilObjTrainingProgramme $node
			 * @var ilTrainingProgrammeUserProgress $progress
			 */
			$progress = $node->getProgressForAssignment($id);
			return $progress->updateFromProgramNode();
		});
		
		return $this;
	}
}

?>