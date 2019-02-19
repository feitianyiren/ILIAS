<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Calendar schedule filter for individual timings
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 *
 * @ingroup ModulesCourse
 */
class ilCalendarScheduleFilterTimings implements ilCalendarScheduleFilter
{
	const CAL_TIMING_START = 1;
	const CAL_TIMING_END = 2;

	/**
	 * @var int
	 */
	private $user_id = 0;


	/**
	 * @var \ilLogger
	 */
	private $logger;

	/**
	 * ilCalendarScheduleFilterTimings constructor.
	 * @param int a_usr_id
	 */
	public function __construct($a_usr_id)
	{
		$this->user_id = $a_usr_id;
		$this->logger = ilLoggerFactory::getLogger('crs');
	}

	/**
	 * Get logger
	 * @return \ilLogger
	 */
	public function getLogger()
	{
		return $this->logger;
	}

	/**
	 * Filter categories
	 * All categories are show no filtering (support for individual folder appointments)
	 * @param int[] $a_cats
	 */
	public function filterCategories(array $a_cats)
	{
		return $a_cats;
	}

	/**
	 * modify event => return false for not presenting event
	 * @param \ilCalendarEntry $a_event
	 * @return bool|ilCalendarEntry
	 */
	public function modifyEvent(ilCalendarEntry $a_event)
	{
		$this->getLogger()->debug('Modifying events for timings');
		if(!$a_event->isAutoGenerated())
		{
			$this->getLogger()->debug($a_event->getTitle().' is not autogenerated => no modification');
			return $a_event;
		}

		if(
			$a_event->getContextId() != ilObjCourse::CAL_COURSE_TIMING_START &&
			$a_event->getContextId() != ilObjCourse::CAL_COURSE_TIMING_END
		)
		{
			$this->getLogger()->debug('Non Timing event: unmodified');
			return $a_event;
		}

		$cat_id = ilCalendarCategoryAssignments::_lookupCategory($a_event->getEntryId());
		$category = $this->isCourseCategory($cat_id);
		if(!$category)
		{
			// return unmodified
			return $a_event;
		}

		// category object type is folder
		$obj_id = $category->getObjId();
		$ref_ids = ilObject::_getAllReferences($obj_id);
		$ref_id = end($ref_ids);

		if(!$this->enabledCourseTimings($ref_id))
		{
			return false;
		}

		
		$active = ilObjectActivation::getTimingsItems($this->getRefId());



		include_once './Services/Object/classes/class.ilObjectActivation.php';
		$item = ilObjectActivation::getItem($ref_id);

		include_once './Services/Object/classes/class.ilObjectActivation.php';
		if($item['timing_type'] != ilObjectActivation::TIMINGS_PRESETTING)
		{
			$this->getLogger()->debug('Delete event with disabled timing settings');
			return FALSE;
		}

		$parent_ref = $item['parent_id'];
		$parent_obj = ilObject::_lookupObjId($parent_ref);


		// if user timing is inactive delete event
		include_once './Modules/Course/classes/Timings/class.ilTimingOptional.php';
		$timing_optional = ilTimingOptional::_getInstance(ilObject::_lookupObjId($parent_ref),$this->user_id);
		if(!$timing_optional->isActive())
		{
			$this->getLogger()->debug('Delete event: inactive timings');
			return false;
		}

		include_once './Modules/Course/classes/class.ilCourseConstants.php';
		if(ilObjCourse::lookupTimingMode($parent_obj) == ilCourseConstants::IL_CRS_VIEW_TIMING_RELATIVE)
		{
			// relative timings => always modify event
			$this->getLogger()->debug('Filtering event since mode is relative timing: ' . $a_event->getPresentationTitle(true));
			return FALSE;
		}
		// timings is absolute
		if($item['changeable'])
		{
			// check if scheduled
			include_once './Modules/Course/classes/Timings/class.ilTimingUser.php';
			$user_data = new ilTimingUser($ref_id, $this->user_id);
			if($user_data->isScheduled())
			{
				$this->getLogger()->debug('Filtering event since item is scheduled by user: ' . $a_event->getPresentationTitle(true));
				return FALSE;
			}
		}

		// valid event => refresh title
		$this->getLogger()->debug('Valid timings event. Update title');
		$a_event->setTitle(ilObject::_lookupTitle($category->getObjId()));


		return $a_event;
	}




	public function addCustomEvents(ilDate $start, ilDate $end, array $a_categories)
	{
		// TODO: Implement addCustomEvents() method.
	}


	/**
	 * @param int $a_category_id
	 * @return bool|ilCalendarCategory
	 */
	protected function isCourseCategory($a_category_id)
	{
		$category = ilCalendarCategory::getInstanceByCategoryId($a_category_id);

		if($category->getType() != ilCalendarCategory::TYPE_OBJ)
		{
			$this->getLogger()->debug('No object calendar => not modifying event.');
			return false;
		}
		if($category->getObjType() != 'crs')
		{
			$this->getLogger()->debug('Category object type is != crs => not modifying event');
			return false;
		}
		return $category;
	}

	/**
	 * Check if timings enabled for ref_id
	 * @param int $a_course_ref
	 * @return bool
	 */
	protected function enabledCourseTimings($a_course_ref)
	{
		if(ilObjCourse::_lookupViewMode(ilObject::_lookupObjId($a_course_ref)) != ilContainer::VIEW_TIMING)
		{
			$this->getLogger()->debug('Parent course has other view mode than timings. course ref_id = ' . $a_course_ref);
			return false;
		}
		return true;
	}
}