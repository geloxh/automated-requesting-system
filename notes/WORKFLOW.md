### System FLow of ARS

### Administrative Forms
- **Overtime Authorization** 
- **Leave Application** 
- **Vehicle Request**
 
### Finance Forms
- **Advance Payment**
- **Request for Payment**
- **Reimbursement** 
- **Liquidation**

### Stages for Administrative Forms
***Requestor (Employee)*** -> ***Checker Approval(Immediate Supervisor)*** -> ***Review Approval(Department Head)*** -> ***Grant Approval Request(Final Approval)*** -> ***Completion of Approval(Employee Request Approved)***

### Stages for Finance Forms
***Requestor (Employee)*** -> ***Checker Approval(Immediate Supervisor)*** -> ***Process Approval(Approval Acquisition)*** -> ***Evaluation Approval (Finance Head)*** -> ***Grant Approval Request(Final Approval)*** -> ***Completion of Approval(Employee Request Approved)***

### Created Account Details
```bash
    cd C:\xampp\mysql\bin
    mysql -u root -p
    USE a;
    SELECT id, full_name, email, role_id FROM employees ORDER BY role_id;
```

```
```
    Role map (align with the employees table):
        1 = SysAdmin
        2 = Approver / Manager (Immediate Supervisor)
        3 = Regular Employee
        4 = Department Head / Finance Head
        5 = Checker / Approval Acquisition
        6 = Final Approver
'''

### Admin Forms
| Step |             Stage            |        Login Account        |       Email           |
|  1   | Requestor — submits the form |          Requestor          |    staff@email.com    |
|  2   |      Checker Approval        |     Immediate Supervisor    |  approver@email.com   |
|  3   |      Review Approval         |       Department Head       |    head@gmail.com     |
|  4   |       Grant Approval         |        Final Approver       |finalapprover@email.com|
|  5   |   Completion of Approval     |         System Admin        |   sysadmin#email.com  |

### Finance Forms
| Step |             Stage            |        Login Account                |       Email           |
|  1   | Requestor — submits the form |          Requestor                  |    staff@email.com    |
|  2   |      Checker Approval        |     Immediate Supervisor            |  approver@email.com   |
|  3   |      Process Approval        |      Finance/Accounting             |   finance@email.com   |
|  4   |     Evaluation Approval      |   Finance Head/Department Head      | financehead@email.com |
|  5   |   Grant Approval Request     |       Final Approver                |finalapprover@email.com|
|  6   |   Completion of Approval     |         System Admin                |   sysadmin#email.com  |

### Role Map

| role_id | Role                              | Description                                                                 |
|---------|-----------------------------------|-----------------------------------------------------------------------------|
| 1       | SysAdmin                          | Full system access. Marks Finance forms as completed after final approval.  |
| 2       | Approver / Immediate Supervisor   | First-level checker. Reviews and approves submitted requests from staff.    |
| 3       | Regular Employee                  | Submits forms/requests. Can only view their own submissions.                |
| 4       | Department Head / Finance Head    | Reviews approved requests. Handles Review (Admin) or Evaluation (Finance).  |
| 5       | Checker / Approval Acquisition    | Finance-only stage. Processes requests between Immediate Head and Finance Head. |
| 6       | Final Approver                    | Last approval authority. Grants final approval on all form types.           |
| 7       | AdminApprover                     | Combined authority covering roles 2, 4, and 6. Can stand in for those stages on specific form types (Vehicle Request: all three; Leave Application & Overtime: role 4 only). |
