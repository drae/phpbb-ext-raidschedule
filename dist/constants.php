<?php

namespace numeric\raidschedule;

class constants {
	const SELECTION_TYPES = 3;
	const RA_UNSELECTED = 0;
	const RA_SELECTED = 1;
	const RA_RESERVE = 2;

	const CAL_EVENT_RAID = 0;
	const CAL_EVENT_OTHER = 1;

	const EVT_CONFIRM = 1000;
	const EVT_RECUR = 1001;
	const EVT_RECUR_ONCE = 0;
	const EVT_RECUR_FUTURE = 1;
	const EVT_RECUR_ALL = 2;

	const TIMELINE_DAYS = '28 days';
}
