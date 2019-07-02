<?php

class qb {

	private $conditions = array();
	private $table_names = array();
	private $read_fields = array();
	private $write_fields = array();
	private $write_values = array();
	private $order_fields = array();
	private $group_fields = array();
	public $limit = 100;
	public $limit_offset = -1;

	public function __construct($table_name = null) {
		if (!is_null($table_name)) {
			$this->add_table($table_name);
		}
		return $this;
	}

	public function reset_tables() {
		$this->table_names = array();
		return true;
	}

	public function set_table($table_name) {
		$this->reset_tables();
		$this->add_table($table_name);
		return $this;
	}

	public function add_table($table_name) {
		$this->table_names[] = $table_name;
		return $this;
	}

	public function reset_write() {
		$this->write_fields = array();
		$this->write_values = array();
		return $this;
	}

	public function add_write($field, $value) {
		$this->write_fields[] = $field;
		$this->write_values[] = $value;
		return $this;
	}

	public function reset_conditions() {
		$this->conditions = array();
		return $this;
	}

	public function add_condition($field, $value, $operator = "=", $value2 = null) {
		if (strpos($field, "`") === false) {
			//$field = "`{$field}`";
		}
		if (strpos($value, "'") === false) {
			//$value = "'{$value}'";
		}
		$this->conditions[] = "{$field} {$operator} {$value}";
		return $this;
	}

	public function reset_read_fields() {
		$this->read_fields = array();
		return $this;
	}

	public function add_read_field($field) {
		$this->read_fields[] = $field;
		return $this;
	}

	public function add_group_field($field) {
		$this->group_fields[] = $field;
		return $this;
	}

	public function add_order($field, $asc = true) {
		$this->order_fields[$field] = array($field, $asc);
		return $this;
	}

	public function select() {
		$read_fields = $this->read_fields;
		if (count($read_fields) == 0) {
			$read_fields[] = "*";
		}
		$limit = null;
		if ($this->limit > 0) {
			$limit = " limit " . ($this->limit_offset != -1 ? $this->limit_offset . ", " : null) . $this->limit;
		}
		$group = null;
		if (count($this->group_fields) > 0) {
			$group = " group by " . implode(", ", $this->group_fields) . " ";
		}
		$order = null;
		if (count($this->order_fields) > 0) {
			$order = " order by ";
			$i = 0;
			foreach ($this->order_fields as $of) {
				$order .= ($i > 0 ? ", " : null) . $of[0] . " " . (is_null($of[1]) ? "" : ($of[1] ? "asc" : "desc"));
				$i++;
			}
		}
		$string = sprintf('select %1$s from %2$s%3$s%6$s%4$s%5$s', implode(", ", $read_fields), implode(", ", $this->table_names), count($this->conditions) > 0 ? (" where " . implode(" and ", $this->conditions)) : null, $order, $limit, $group);
		return $string;
	}

	public function update() {
		$updates = array();
		for ($d = 0, $m = count($this->write_fields); $d < $m; $d++) {
			$updates[] = $this->write_fields[$d] . "='" . $this->write_values[$d] . "'";
		}
		$update_fields = implode(", ", $updates);
		$limit = null;
		if ($this->limit > 0) {
			$limit = " limit " . ($this->limit_offset != -1 ? $this->limit_offset . ", " : null) . $this->limit;
		}
		$where = count($this->conditions) > 0 ? (" where " . implode(" and ", $this->conditions)) : null;
		$string = sprintf('update %1$s set %2$s %3$s %4$s', $this->table_names[0], $update_fields, $where, $limit);
		return $string;
	}

	public function insert() {
		$table = $this->table_names[0];
		$fields = "`" . implode("`, `", $this->write_fields) . "`";
		$values = "'" . implode("', '", $this->write_values) . "'";
		$string = sprintf('insert into %1$s (%2$s) values(%3$s)', $table, $fields, $values);
		return $string;
	}

}

?>