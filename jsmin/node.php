<?php

abstract class Node {
	static protected $seq = 0;

	protected $id;
	protected $nodes = array();
	protected $parent;
	protected $removed = false;

	protected $debug = true;

	public function visit(AST $ast, Node $parent = null) {
		throw new Exception('Node::visit( ) Not implemented in ' . get_class($this));
	}

	public function collectStatistics(AST $ast) {
		throw new Exception('Node::collectStatistics( ) Not implemented in ' . get_class($this));
	}

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
		}
	}

	public function toString() {
		return get_class($this);
	}

	public function __toString() {
		return (string)$this->toString();
	}

	public function optimizeBreak() {
		return $this;
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

	public function optimize() {
		return $this;
	}

	public function last() {
		return $this;
	}

	public function isVoid() {
		return false;

		return $this instanceof VoidExpression && $this->isEmpty();
	}

	public function isRedundant() {
		return false;
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

	public function declarations() {
		return array();
	}

	public function breaking() {
		return null;
	}

	// this node has disappeared. Notify all subnodes
	public function gone() {
		throw new Exception('Node::gone( ) Not implemented in ' . get_class($this));
	}

	public function nodes() {
		return array($this);
	}

	public function countLetters(&$letters) {
		throw new Exception('Node::countLetters( &$letters ) Not implemented in ' . get_class($this));
	}

	public function removeUseless() {
		return $this;
	}

	public function counter($string, &$letters) {
		for ($i = 0, $length = strlen($string); $i !== $length; ++$i) {
			if (isset($letters[$string[$i]])) {
				$letters[$string[$i]] += 1;
			}
		}
	}

	public function moveExpression(Expression $n) {
		return false;
	}

	/**
	 * Check if a node contains a breaking statement (return, continue, break, throw)
	 * @return boolean
	 */
	public function isBreaking() {
		return false;
	}
}
