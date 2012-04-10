<?php

abstract class Node {
	static protected $seq = 0;

	protected $id;
	protected $nodes = array();
	protected $parent;
	protected $removed = false;

	protected $debug = true;

	abstract public function visit(AST $ast);

	abstract public function collectStatistics(AST $ast);

	public function __construct() {
		$this->id = self::$seq++;

		if ($this instanceof ScriptNode) {
			$this->debug = true;
		}

		foreach($this->nodes as $n) {
			if (!$n) {
				tracer();
				exit;
			}

			$n->parent($this);
		}
	}

	public function toString() {
		return get_class($this);
	}

	public function __toString() {
		return (string)$this->toString();
	}

	public function parent(Node $p = null) {
		if ($p !== null) {
			$this->parent = $p;
		}

		if (!$this->parent) {
			throw new RuntimeException('No parent known.');
			return $this;
		}

		return $this->parent;
	}

	public function first() {
		if (!$this->nodes) {
			return $this;
		} else {
			return $this->nodes[0]->first();
		}
	}

	public function isConstant() {
		return false;
	}

	public function isVoid() {
		return $this instanceof VoidExpression && $this->isEmpty();
	}

	public function isRedundant() {
		return false;
	}

	public function validIdentifier($n) {
		return !isset(Scope::$reserved[$n]) && preg_match('~\A[$_a-zA-Z]+[$_a-zA-Z0-9]*\z~', $n) === 1;
	}

	public function asBlock() {
		if ($this instanceof BlockStatement) {
			return $this;
		}

		return new BlockStatement(array($this));
	}

	public function hasSideEffects() {
		return true;
	}

	public function hasStructure(Node $cmp) {
		return $cmp instanceof static;
	}
}
