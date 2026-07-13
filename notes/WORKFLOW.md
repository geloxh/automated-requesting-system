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