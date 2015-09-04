<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Table/classes/class.ilTable2GUI.php';

/**
 * Class ilTestPassOverviewTableGUI
 */
class ilTestPassOverviewTableGUI extends ilTable2GUI
{
	const CONTEXT_SHORT = 1;
	const CONTEXT_LONG  = 2;

	/**
	 * @var bool
	 */
	protected $pdf_view = false;

	/**
	 * @var bool
	 */
	protected $objectiveOrientedPresentationEnabled = false;

	/**
	 * @param        $parent
	 * @param string $cmd
	 * @param int    $context
	 */
	public function __construct($parent, $cmd, $context = self::CONTEXT_SHORT, $pdf_view = false)
	{
		$this->pdf_view = $pdf_view;
		
		$this->setId('tst_pass_overview_' . $context . '_' . $parent->object->getId());
		$this->setDefaultOrderField('pass');
		$this->setDefaultOrderDirection('ASC');

		parent::__construct($parent, $cmd, $context);
		
		// Don't set any limit because of print/pdf views. Furthermore, this view is part of different summary views, and no cmd ist passed to he calling method.
		$this->setLimit(PHP_INT_MAX);
		
		if($this->pdf_view)
		{
			$this->disable('linkbar');
			$this->disable('numinfo');
			$this->disable('numinfo_header');
			$this->disable('hits');
		}
		$this->disable('sort');

		$this->setRowTemplate('tpl.il_as_tst_pass_overview_row.html', 'Modules/Test');
	}
	
	public function init()
	{
		$this->initColumns();
	}

	/**
	 * @param string $field
	 * @return bool
	 */
	public function numericOrdering($field)
	{
		switch($field)
		{
			case 'pass':
			case 'date':
			case 'percentage':
				return true;
		}

		return false;
	}

	/**
	 * @param array $row
	 */
	public function fillRow(array $row)
	{
		if(array_key_exists('percentage', $row))
		{
			$row['percentage'] = sprintf('%.2f', $row['percentage']) . '%';
		}

		// fill columns
		
		if( !$this->isObjectiveOrientedPresentationEnabled() )
		{
			$this->tpl->setVariable('VAL_SCORED', $row['scored'] ? '&otimes;' : '');
			$this->tpl->setVariable('VAL_PASS', $row['pass']);
		}
		
		$this->tpl->setVariable('VAL_DATE', $this->formatDate($row['date']));

		if( $this->isObjectiveOrientedPresentationEnabled() )
		{
			$this->tpl->setVariable('VAL_LO_OBJECTIVES', $row['objectives']);
			
			$this->tpl->setVariable('VAL_LO_TRY', sprintf(
				$this->lng->txt('tst_res_lo_try_n'), $row['pass']
			));
		}

		$this->tpl->setVariable('VAL_ANSWERED', $this->buildWorkedThroughQuestionsString(
			$row['num_workedthrough_questions'], $row['num_questions_total']
		));
		
		if( isset($row['hints']) )
		{			
			$this->tpl->setVariable('VAL_HINTS', $row['hints']);
		}
		
		$this->tpl->setVariable('VAL_REACHED', $this->buildReachedPointsString(
			$row['reached_points'], $row['max_points']
		));

		$this->tpl->setVariable('VAL_PERCENTAGE', $row['percentage']);

		if(!$this->pdf_view)
		{
			$this->tpl->setVariable('VAL_PASS_DETAILS', $row['pass_details']);
		}
	}

	/**
	 *
	 */
	protected function initColumns()
	{
		if(self::CONTEXT_LONG == $this->getContext() && !$this->isObjectiveOrientedPresentationEnabled())
		{
			$this->addColumn($this->lng->txt('scored_pass'), '', '150');
		}
		
		if( !$this->isObjectiveOrientedPresentationEnabled() )
		{
			$this->addColumn($this->lng->txt('pass'), '', '1%');
		}
		
		$this->addColumn($this->lng->txt('date'));
		
		if( $this->isObjectiveOrientedPresentationEnabled() )
		{
			$this->addColumn($this->lng->txt('tst_res_lo_objectives_header'), '');
			$this->addColumn($this->lng->txt('tst_res_lo_try_header'), '');
		}
		
		if(self::CONTEXT_LONG == $this->getContext())
		{
			$this->addColumn($this->lng->txt('tst_answered_questions'));
			if($this->getParentObject()->object->isOfferingQuestionHintsEnabled())
			{
				$this->addColumn($this->lng->txt('tst_question_hints_requested_hint_count_header'));
			}
			$this->addColumn($this->lng->txt('tst_reached_points'));
			$this->addColumn($this->lng->txt('tst_percent_solved'));
		}
		// pass details menu
		if(!$this->pdf_view)
		{
			$this->addColumn('', '', '10%' );
		}
	}

	/**
	 * @return boolean
	 */
	public function isObjectiveOrientedPresentationEnabled()
	{
		return $this->objectiveOrientedPresentationEnabled;
	}

	/**
	 * @param boolean $objectiveOrientedPresentationEnabled
	 */
	public function setObjectiveOrientedPresentationEnabled($objectiveOrientedPresentationEnabled)
	{
		$this->objectiveOrientedPresentationEnabled = $objectiveOrientedPresentationEnabled;
	}

	/**
	 * @param integer $dateTS
	 * @return string $dateFormated
	 */
	private function formatDate($date)
	{
		$oldValue = ilDatePresentation::useRelativeDates();
		ilDatePresentation::setUseRelativeDates(false);
		$date = ilDatePresentation::formatDate(new ilDateTime($date, IL_CAL_UNIX));
		ilDatePresentation::setUseRelativeDates($oldValue);
		return $date;
	}
	
	private function buildWorkedThroughQuestionsString($numQuestionsWorkedThrough, $numQuestionsTotal)
	{
		return "{$numQuestionsWorkedThrough} {$this->lng->txt('of')} {$numQuestionsTotal}";
	}
	
	private function buildReachedPointsString($reachedPoints, $maxPoints)
	{
		return "{$reachedPoints} {$this->lng->txt('of')} {$maxPoints}";
	}
}