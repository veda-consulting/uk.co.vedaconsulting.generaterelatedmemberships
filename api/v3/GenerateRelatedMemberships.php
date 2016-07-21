<?php

// api function to generate related memberships daily
function civicrm_api3_generate_related_memberships_refresh($params) {

  // get all contacts with memberships
  $memberships = civicrm_api3('Membership', 'get', array(
    'sequential' => 1,
    'return' => "contact_id",
    'active_only' => 1,
    'options' => array('limit' => ""),
  ));

  // if error in getting membership contacts return error
  if ($memberships['is_error'] || empty($memberships['values'])) {
    return civicrm_api3_create_error($memberships);
  }

  // loop through each membership and create related memberships
  foreach ($memberships['values'] as $key => $membership) {
    $contactID = $membership['contact_id'];

    // update related memberships
    $dao = new CRM_Member_DAO_Membership();
    $dao->contact_id = $contactID;
    $dao->is_test = 0;
    $dao->find();

    //checks membership of contact itself
    while ($dao->fetch()) {
      $relationshipTypeId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $dao->membership_type_id, 'relationship_type_id', 'id');
      if ($relationshipTypeId) {
        $membershipParams = array(
          'id' => $dao->id,
          'contact_id' => $dao->contact_id,
          'membership_type_id' => $dao->membership_type_id,
          'join_date' => CRM_Utils_Date::isoToMysql($dao->join_date),
          'start_date' => CRM_Utils_Date::isoToMysql($dao->start_date),
          'end_date' => CRM_Utils_Date::isoToMysql($dao->end_date),
          'source' => $dao->source,
          'status_id' => $dao->status_id,
        );

        // create/update memberships for related contacts
        CRM_Member_BAO_Membership::createRelatedMemberships($membershipParams, $dao);
      } // end of if relationshipTypeId
    }

  }

  return civicrm_api3_create_success('success', $params, 'GenerateRelatedMemberships', 'refresh');

}


?>
