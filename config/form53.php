<?php

/**
 * Form No. 53 (Abstract / D-36) structure, taken from the report template of the Monthly report.
 *   demands : operating head of the budget code (e.g. "2406/26") => titles printed on the form
 *   schemes : scheme name (as in the budget codes) => the minor head and sub-head it is printed under
 * A scheme that is not listed here is printed under "Other schemes" of its demand, so the form total always equals the real expenditure.
 */
$minor101 = ['Minor Head: 101 Forest consarvation development and regeneration', '2406  1  101'];
$minor796 = ['Minor Head: 796 Trible Area Sub Plan', '2406  1  796'];
$minor4101 = ['Minor Head: 101 Forest consarvation and Dev.', '4406  1  101'];

return [
    'order' => ['2406/26', '2406/95', '2406/96', '4406/26', '4406/95', '4406/96'],
    'sector' => '(c) Economic service',
    'sub_sector' => '(a) Agriculture and Allied service',
    'sub_major_head' => '01 Forestry',
    'demands' => [
        '2406/26' => ['demand_title' => '26 (Revenue) / 2406 Forestry and Wildlife', 'demand_no' => '26 (Revenue)', 'major_head' => '2406 Forestry and Wildlife'],
        '2406/95' => ['demand_title' => '95 (Scheduled Castes Sub Plan) / 2406 Forestry and Wildlife', 'demand_no' => '95 (Scheduled Castes Sub Plan)', 'major_head' => '2406 Forestry and Wildlife'],
        '2406/96' => ['demand_title' => '96 (Revenue) / 2406 Forestry and Wildlife', 'demand_no' => '96 (Revenue)', 'major_head' => '2406 Forestry and Wildlife'],
        '4406/26' => ['demand_title' => '26 (Capital) / 4406 Capital Outlay on Forestry and Wildlife', 'demand_no' => '26 (Capital)', 'major_head' => '4406 Capital Outlay on Forestry and Wildlife'],
        '4406/95' => ['demand_title' => '95 (Scheduled Castes Sub Plan) / 4406 Capital Outlay on Forestry and Wildlife', 'demand_no' => '95 (Scheduled Castes Sub Plan)', 'major_head' => '4406 Capital Outlay on Forestry and Wildlife'],
        '4406/96' => ['demand_title' => '096 Capital / 4406 Capital Outlay on Forestry and Wildlife', 'demand_no' => '096 Capital', 'major_head' => '4406 Capital Outlay on Forestry and Wildlife'],
    ],
    // scheme => [minor name, minor code, sub-head name, sub-head code]
    'schemes' => [
        '02- Divisional' => [...['Minor Head: 001 Direction and Administration', '2406  1  1'], '(02) Divisional Offices', '2406  1  1  2'],
        '02- Divisional (Charged)' => [...['Minor Head: 001 Direction and Administration', '2406  1  1'], '(02) Divisional (Charged)', '2406  1  1  2'],
        '03- Building' => [...['Minor Head: 070 Communication and Building', '2406  1  70'], '(03) Building Grass Godown and Comyu. Mants.', '2406  1  70  3'],
        '15-Forest Research (NT)' => [...['Minor Head: 005 Survey and Utilization of forest Resources', '2406  1  5'], '01-FST-15 F.R.T.O and P.', '2406  1  5  1'],
        '17- Gujarat Community Forestry Scheme NT' => [...$minor101, '17 Gujarat Community Forestry Project', '2406  1  101  17'],
        '2406-26 Implementation of Mahatma Gandhi National Rural Guarantee Act' => [...$minor101, '(18) Implementation of Mahatma Gandhi National Rural Guarantee Act', '2406  1  101  18'],
        '2406/26 Mgmt and Development of Wildlife (NT)' => [...['Minor Head: 110 Wild life', '2406  2  110'], '02 Management and Development of Wildlife', '2406  2  110  2'],
        '05 Scheduled Castes Sub-Plan (SCSP)' => [...['Minor Head: 789 Special Component Plan for Scheduled Castes', '2406  1  789'], '05 Scheduled Castes Sub-Plan (SCSP)', '2406  1  789  05'],
        '17- FST- 9 Gujarat Community Forestry Project (Trible)' => [...$minor796, '17 FST-9 Gujarat Community Forestry Project', '2406  1  796  17'],
        '35 Community Forestry Project (Trible)' => [...$minor796, '35 Community Forestry Project', '2406  1  796  35'],
        '10- FST- 8 Community Forestry Scheme' => [...$minor4101, '10- FST- 8 Community Forestry Scheme', '4406  1  101  10'],
        'FST- 8 Scheduled Castes Sub Plan Scheme for Fruit Plantations' => [...$minor4101, '(01) FST-8 Scheduled Castes Sub Plan Scheme for Fruit Plantations', '4406  1  101  1'],
        '06- FST- 8 Community Forestry Scheme (Trible)' => [...['Minor Head: 796 Trible Area Sub Plan', '4406  1  796'], '06- Fst-08 Gujarat Community Forestry Project', '4406  1  796  6'],
    ],
    'other_minor' => ['Minor Head: Other schemes (not in the Form 53 template - minor head to be confirmed)', ''],
];
