<?php
/**
 * @description EcommerceRole provides customisations to the {@link Member}
 * class specifically for this ecommerce module.
 *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package ecommerce
 * @sub-package member
 *
 **/

class EcommerceRole extends DataObjectDecorator {

	/**
	 *
	 *@var Boolean - $automatic_membership = automatically add new customer as a member.
	 **/
	protected static $automatic_membership = true;
		static function set_automatic_membership($b){self::$automatic_membership = $b;}
		static function get_automatic_membership(){return self::$automatic_membership;}
	/**
	 *
	 *@var Boolean - $automatically_update_member_details = automatically update member details for a logged-in user.
	 **/
	protected static $automatically_update_member_details = false;
		static function set_automatically_update_member_details($b){self::$automatically_update_member_details = $b;}
		static function get_automatically_update_member_details(){return self::$automatically_update_member_details;}

	function extraStatics() {
		return array(
			'db' => array(
				'Notes' => 'HTMLText'
			),
			'has_many' => array(
				"Orders" => "Order"
			)
		);
	}


	protected static $customer_group_code = 'shop_customers';
		static function set_customer_group_code(string $s) {self::$customer_group_code = $s;}
		static function get_customer_group_code() {return self::$customer_group_code;}

	protected static $customer_group_name = "shop customers";
		static function set_customer_group_name(string $s) {self::$customer_group_name = $s;}
		static function get_customer_group_name() {return self::$customer_group_name;}

	protected static $customer_permission_code = "SHOP_CUSTOMER";
		static function set_customer_permission_code(string $s) {self::$customer_permission_code = $s;}
		static function get_customer_permission_code() {return self::$customer_permission_code;}


	/**
	 *@return DataObject (Group)
	 **/
	public function get_customer_group() {
		return DataObject::get_one("Group", "\"Code\" = '".self::get_customer_group_code()."' OR \"Title\" = '".self::get_customer_group_name()."'");
	}

/*******************************************************
   * SHOP ADMIN
*******************************************************/


	protected static $admin_group_code = "shop_administrators";
		static function set_admin_group_code(string $s) {self::$admin_group_code = $s;}
		static function get_admin_group_code() {return self::$admin_group_code;}

	protected static $admin_group_name = "shop administrators";
		static function set_admin_group_name(string $s) {self::$admin_group_name = $s;}
		static function get_admin_group_name() {return self::$admin_group_name;}

	protected static $admin_permission_code = "SHOP_ADMIN";
		static function set_admin_permission_code(string $s) {self::$admin_permission_code = $s;}
		static function get_admin_permission_code() {return self::$admin_permission_code;}

	protected static function add_members_to_customer_group() {
		$gp = DataObject::get_one("Group", "\"Title\" = '".self::get_customer_group_name()."'");
		if($gp) {
			$allCombos = DB::query("
				SELECT \"Group_Members\".\"ID\", \"Group_Members\".\"MemberID\", \"Group_Members\".\"GroupID\"
				FROM \"Group_Members\"
				WHERE \"Group_Members\".\"GroupID\" = ".$gp->ID.";"
			);
			//make an array of all combos
			$alreadyAdded = array();
			$alreadyAdded[-1] = -1;
			if($allCombos) {
				foreach($allCombos as $combo) {
					$alreadyAdded[$combo["MemberID"]] = $combo["MemberID"];
				}
			}
			$unlistedMembers = DataObject::get(
				"Member",
				$where = "\"Member\".\"ID\" NOT IN (".implode(",",$alreadyAdded).")",
				$sort = "",
				$join = "INNER JOIN \"Order\" ON \"Order\".\"MemberID\" = \"Member\".\"ID\""
			);

			//add combos
			if($unlistedMembers) {
				$existingMembers = $gp->Members();
				foreach($unlistedMembers as $member) {
					$existingMembers->add($member);
				}
			}
		}
	}

	/**
	 * get CMS fields describing the member in the CMS when viewing the order.
	 *
	 * @return Field / ComponentSet
	 **/

	public function getEcommerceFieldsForCMS() {
		$fields = new CompositeField();
		$memberTitle = new TextField("MemberTitle", "Name", $this->owner->getTitle());
		$fields->push($memberTitle->performReadonlyTransformation());
		$memberEmail = new TextField("MemberEmail","Email", $this->owner->Email);
		$fields->push($memberEmail->performReadonlyTransformation());
		$lastLogin = new TextField("MemberLastLogin","Last login",$this->owner->dbObject('LastVisited')->Nice());
		$fields->push($lastLogin->performReadonlyTransformation());
		if($group = EcommerceRole::get_customer_group()) {
			$fields->push(new LiteralField("EditMembers", '<p><a href="/admin/security/show/'.$group->ID.'/">view (and edit) all customers</a></p>'));
		}
		return $fields;
	}



	/**
	 *
	 * @return FieldSet
	 */
	function getEcommerceFields() {
		$fields = new FieldSet(
			//new HeaderField(_t('EcommerceRole.PERSONALINFORMATION','Personal Information'), 3),
			//new TextField('FirstName', _t('EcommerceRole.FIRSTNAME','First Name')),
			//new TextField('Surname', _t('EcommerceRole.SURNAME','Surname')),
			//new EmailField('Email', _t('EcommerceRole.EMAIL','Email'))
		);
		$this->owner->extend('augmentEcommerceFields', $fields);
		return $fields;
	}

	/**
	 * Return which member fields should be required on {@link OrderForm}
	 * and {@link ShopAccountForm}.
	 *
	 * @return array
	 */
	function getEcommerceRequiredFields() {
		$fields = array(
			//'FirstName',
			//'Surname',
			//'Email'
		);
		$this->owner->extend('augmentEcommerceRequiredFields', $fields);
		return $fields;
	}


	//this method needs to be tested!
	public function onAfterWrite() {
		parent::onAfterWrite();
		self::add_members_to_customer_group();
	}

	public function requireDefaultRecords() {
		parent::requireDefaultRecords();

	}

	/**
	 *@return Boolean
	 **/
	function IsShopAdmin() {
		if($this->owner->IsAdmin()) {
			return true;
		}
		else{
			return Permission::checkMember($this->owner, self::get_admin_permission_code());
		}
	}

	function populateDefaults() {
		parent::populateDefaults();
	}

}


