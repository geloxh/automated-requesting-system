<!-- This notes is for the implementation of approval worklow feature-->


app/controllers
	  ApprovalController.php
		Responsibilities:
		Submit request for approval
		Move request to next approval level
		Approve / reject actions
		Fetch approval status

app/middleware
	CheckApprovalLevel.php
	RequireApprover.php
		Responsibilities:
		Ensure only the correct level approver can act
		Block unauthorized approvals

app/helpers
	approval_helper.php
		Responsibilities:
		Determine next approval level
		Format approval status
		Common approval-related functions

routes/
	You’ll need to define endpoints for the workflow.

	Example routes:
	/approval/submit
	/approval/{id}
	/approval/{id}/approve
	/approval/{id}/reject

	These map to your ApprovalController.

views/
	UI for interacting with the workflow.

	Example files:
	approval_form.php → submit request
	approval_list.php → list pending approvals
	approval_detail.php → show approval steps/status

config/
	If your approval levels are structured/configurable:

	Example:
	approval.php
	return [
  		'levels' => [
   		 1 => 'Manager',
   		 2 => 'Director',
    		3 => 'CEO'
  			]
		];

public/(optional)
	Only if you need:
	JS for dynamic approval UI
	CSS for styling

Database/ (important)

	requests
	approval_steps
	approvals
	Basic idea:
	A request moves through multiple approval levels
	Each level is tracked separately

<!-- Overall idea -->
Simple flow (how it works)
	User submits → ApprovalController@submit
	Request stored with level = 1
	Level 1 approver sees it in dashboard
	Approver clicks approve:
	If not last level → move to next level
	If last level → mark as fully approved
	Middleware ensures correct approver is acting
