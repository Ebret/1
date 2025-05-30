# 🌟 ExtremeLife MLM Sponsor System - Testing Guide

## 📋 **SPONSOR SYSTEM OVERVIEW**

The ExtremeLife MLM system now includes a comprehensive **sponsor relationship system** that enables proper MLM testing with realistic member hierarchies, commission calculations, and referral tracking.

## 🏗️ **SYSTEM ARCHITECTURE**

### **Enhanced Database Schema:**
- **`mlm_members`** - Enhanced with sponsor relationships and referral codes
- **`mlm_genealogy`** - Tree structure for upline/downline tracking
- **`mlm_sponsor_commissions`** - Multi-level commission tracking
- **`register.php`** - Member registration with sponsor validation

### **MLM Hierarchy Structure:**
```
Level 1: ExtremeLife Company (ELH000001) - Diamond [₱50,000 sales]
Level 2: ├── Maria Santos (ELH000002) - VIP [₱25,000 sales]
         └── Juan Dela Cruz (ELH000003) - VIP [₱30,000 sales]
Level 3: ├── Ana Garcia (ELH000004) - Distributor [₱15,000 sales]
         └── Carlos Rodriguez (ELH000005) - Distributor [₱18,000 sales]
Level 4: ├── Sofia Reyes (ELH000006) - Wholesale [₱8,000 sales]
         ├── Miguel Torres (ELH000007) - Wholesale [₱6,500 sales]
         └── Demo Member (ELH000010) - Member [₱2,500 sales]
Level 5: ├── Isabella Morales (ELH000008) - Member [₱3,500 sales]
         └── Diego Fernandez (ELH000009) - Member [₱2,800 sales]
```

## 🧪 **TESTING SCENARIOS**

### **1. Member Dashboard Testing**
**URL:** `/member_dashboard.php`
**Default Member:** Demo Member (ID: 10, ELH000010)

**Expected Results:**
- ✅ **Sponsor Information**: Shows Carlos Rodriguez (ELH000005) as sponsor
- ✅ **Member Profile**: Complete profile with referral code ELH000010
- ✅ **Commission Data**: ₱250.00 total commissions, ₱25.00 rebates
- ✅ **Team Size**: 0 direct referrals (new member)
- ✅ **Genealogy Tree**: Visual tree showing sponsor relationship
- ✅ **Performance Metrics**: Sales volume, team data, growth rates

### **2. Member Registration Testing**

#### **A. Registration with Sponsor Code**
**URL:** `/register.php?sponsor=ELH000005`
**Test Data:**
```
First Name: Test
Last Name: User
Email: test@extremelifeherbal.com
Phone: +63 912 345 6789
Sponsor Code: ELH000005 (Carlos Rodriguez)
```

**Expected Results:**
- ✅ **Sponsor Validation**: Shows Carlos Rodriguez's information
- ✅ **Registration Success**: Creates new member with unique referral code
- ✅ **Genealogy Update**: Adds member to Carlos's downline
- ✅ **Commission Setup**: Ready for commission calculations

#### **B. Direct Registration (No Sponsor)**
**URL:** `/register.php`
**Expected Results:**
- ✅ **Manual Sponsor Entry**: User must enter sponsor code manually
- ✅ **Sponsor Validation**: Real-time validation of sponsor codes
- ✅ **Error Handling**: Clear error messages for invalid codes

### **3. Commission Calculation Testing**

#### **Test Sale Scenario:**
- **Member**: Demo Member (ELH000010)
- **Sale Amount**: ₱1,000.00
- **Expected Commissions**:
  - Demo Member (Direct): ₱100.00 (10% commission) + ₱20.00 (2% rebate) = ₱120.00
  - Carlos Rodriguez (Sponsor): ₱20.00 (2% sponsor bonus)
  - Maria Santos (Level 2): ₱10.00 (1% indirect bonus)
  - ExtremeLife Company (Level 3): ₱5.00 (0.5% indirect bonus)

### **4. Sponsor Code Validation Testing**

#### **Valid Sponsor Codes:**
- `ELH000001` - ExtremeLife Company (Root)
- `ELH000002` - Maria Santos (VIP)
- `ELH000005` - Carlos Rodriguez (Distributor)
- `ELH000006` - Sofia Reyes (Wholesale)

#### **Invalid Sponsor Codes:**
- `ELH000999` - Non-existent code
- `ELH000000` - Invalid format
- `INVALID123` - Wrong format

## 🔧 **TESTING PROCEDURES**

### **Step 1: Database Setup**
```bash
# Run sponsor system setup (if database is available)
php create_sponsor_system.php

# Or run standalone test
php test_sponsor_system.php
```

### **Step 2: Member Dashboard Testing**
1. Visit `/member_dashboard.php`
2. Verify Demo Member data displays correctly
3. Check sponsor information shows Carlos Rodriguez
4. Confirm genealogy tree displays properly
5. Validate commission and rebate calculations

### **Step 3: Registration Flow Testing**
1. **With Sponsor**: Visit `/register.php?sponsor=ELH000005`
   - Verify sponsor info displays
   - Complete registration form
   - Confirm success message with new referral code
   
2. **Manual Sponsor Entry**: Visit `/register.php`
   - Enter sponsor code manually
   - Test validation (try invalid codes)
   - Complete registration with valid sponsor

### **Step 4: Commission System Testing**
1. Create test order for Demo Member
2. Run commission calculation
3. Verify multi-level commissions are calculated
4. Check sponsor earnings are recorded

## 📊 **TEST DATA REFERENCE**

### **Available Test Members:**
| ID | Name | Code | Rank | Sponsor | Sales |
|----|------|------|------|---------|-------|
| 1 | ExtremeLife Company | ELH000001 | Diamond | None | ₱50,000 |
| 2 | Maria Santos | ELH000002 | VIP | ELH000001 | ₱25,000 |
| 3 | Juan Dela Cruz | ELH000003 | VIP | ELH000001 | ₱30,000 |
| 4 | Ana Garcia | ELH000004 | Distributor | ELH000002 | ₱15,000 |
| 5 | Carlos Rodriguez | ELH000005 | Distributor | ELH000002 | ₱18,000 |
| 6 | Sofia Reyes | ELH000006 | Wholesale | ELH000004 | ₱8,000 |
| 7 | Miguel Torres | ELH000007 | Wholesale | ELH000004 | ₱6,500 |
| 8 | Isabella Morales | ELH000008 | Member | ELH000006 | ₱3,500 |
| 9 | Diego Fernandez | ELH000009 | Member | ELH000006 | ₱2,800 |
| 10 | Demo Member | ELH000010 | Member | ELH000005 | ₱2,500 |

### **Commission Structure:**
- **Direct Commission**: Based on member's rank (10%-20%)
- **Rebate**: Based on member's rank (2%-6%)
- **Sponsor Bonus**: 2% for direct sponsor
- **Level 2 Bonus**: 1% for sponsor's sponsor
- **Level 3 Bonus**: 0.5% for level 3 upline

## 🎯 **SUCCESS CRITERIA**

### **✅ Member Dashboard:**
- [ ] Displays correct member information
- [ ] Shows sponsor details accurately
- [ ] Genealogy tree renders properly
- [ ] Commission data is calculated correctly
- [ ] Responsive design works on mobile

### **✅ Registration System:**
- [ ] Sponsor code validation works
- [ ] Registration creates proper relationships
- [ ] Unique referral codes are generated
- [ ] Error handling for invalid data

### **✅ Commission System:**
- [ ] Multi-level commissions calculate correctly
- [ ] Sponsor bonuses are distributed properly
- [ ] Database records are created accurately
- [ ] Commission totals update member records

## 🚀 **DEPLOYMENT CHECKLIST**

1. **Database Tables**: Ensure all MLM tables exist
2. **File Permissions**: Set proper permissions (644) for PHP files
3. **Member Data**: Populate with test member hierarchy
4. **Commission Setup**: Configure commission rates and bonuses
5. **Testing**: Verify all sponsor relationships work correctly

## 📞 **TROUBLESHOOTING**

### **Common Issues:**
- **Database Connection**: Check connection settings in PHP files
- **Missing Tables**: Run `create_sponsor_system.php` to create schema
- **Permission Errors**: Ensure www-data owns PHP files
- **Sponsor Validation**: Verify sponsor codes exist in database

### **Debug Commands:**
```bash
# Check database tables
mysql -u drupal_user -p drupal_umd -e "SHOW TABLES LIKE 'mlm_%'"

# Test PHP syntax
php -l member_dashboard.php
php -l register.php

# Run sponsor system test
php test_sponsor_system.php
```

---

**🌟 ExtremeLife MLM Sponsor System - Ready for Comprehensive Testing! 🌟**

*Complete MLM hierarchy with realistic sponsor relationships, commission calculations, and member registration flow.*
