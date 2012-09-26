<?php
class ObjectExpression extends Expression {
	public function __construct(array $entries) {
		$this->nodes = $entries;

		parent::__construct();
	}

	public function visit(AST $ast, Node $parent = null) {
		foreach($this->nodes as $i => $e) {
			$this->nodes[$i] = $e->visit($ast, $this);
		}

		return $this;
	}

	public function collectStatistics(AST $ast) {
		foreach($this->nodes as $e) {
			$e->collectStatistics($ast);
		}
	}

	public function toString() {
		$output = array();
		foreach($this->nodes as $n) {
			$output[] = $n->toString();
		}

		if (AST::$options['beautify']) {
			$output = implode(",\n", $output);
			if (trim($output) === '') {
				return '{}';
			}

			return "{\n" . Stream::indent($output) . "\n}";
		}

		return '{' . implode(',', $output) . '}';
	}

	public function type() {
		return 'object';
	}

	public function gone() {
		foreach($this->nodes as $n) {
			$n->gone();
		}
	}

	public function precedence() {
		return null;
	}

	public function countLetters(&$letters) {
		foreach($this->nodes as $n) {
			$n->countLetters($letters);
		}
	}

	public function hasSideEffects() {
		foreach($this->nodes as $n) {
			if ($n->hasSideEffects()) {
				return true;
			}
		}

		return false;
	}

	public function debug() {
		$out = array();
		foreach($this->nodes as $n) {
			$out[] = $n->debug();
		}

		return "{\n" . preg_replace('~^~m', '    ', implode("\n", $out)) . "\n}";
	}
}
