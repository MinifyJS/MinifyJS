<?php
class ArrayExpression extends Expression {
	public function __construct(array $entries) {
		$this->nodes = $entries;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$nodes = array();
		foreach($this->nodes as $i => $e) {
			$nodes[] = $e->visit($ast);
		}

		return new ArrayExpression($nodes);
	}

	public function collectStatistics(AST $ast) {
		foreach($this->nodes as $e) {
			$e->collectStatistics($ast);
		}
	}

	public function gone() {
		foreach($this->nodes as $n) {
			$n->gone();
		}
	}

	public function toString() {
		$options = array();
		foreach($this->nodes as $n) {
			$v = $n->toString();
			$options[] = $n instanceof CommaExpression ? '(' . $v . ')' : $v;
		}

		return '[' . implode(',' . (AST::$options['beautify'] ? ' ' : ''), $options) . ']';
	}

	public function type() {
		return 'object';
	}

	public function actualType() {
		return 'array';
	}

	public function precedence() {
		return 0;
	}

	public function asBoolean() {
		if (!$this->nodes) {
			return true;
		}
	}

	public function isConstant() {
		foreach($this->nodes as $n) {
			if (!$n->isConstant()) {
				return false;
			}
		}

		return true;
	}

	public function asString() {
		$options = array();
		foreach($this->nodes as $n) {
			if (null == $v = $n->asString()) {
				return null;
			}

			$options[] = $v;
		}

		return implode(',', $options);
	}

	public function asNumber() {
		if (!$this->nodes) {
			return 0;
		}

		if (count($this->nodes) === 1) {
			return $this->nodes[0]->asNumber();
		}
	}

	public function countLetters(&$letters) {
		foreach ($this->nodes as $n) {
			$n->countLetters($letters);
		}
	}

	public function debug() {
		$out = array();
		foreach($this->nodes as $n) {
			$out[] = $n->debug();
		}

		return "[\n" . preg_replace('~^~m', '    ', implode("\n", $out)) . "\n]";
	}
}
