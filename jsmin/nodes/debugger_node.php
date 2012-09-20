<?php
class DebuggerNode extends Node {
	public function visit(AST $ast, Node $parent = null) {
		if (AST::$options['strip-debug']) {
			return new VoidExpression(new Number(0));
		}

		return $this;
	}

	public function collectStatistics(AST $ast) {}

	public function countLetters(&$letters) {
		foreach(array('d', 'e', 'b', 'u', 'g', 'g', 'e', 'r') as $l) {
			$letters[$l] += 1;
		}
	}

	public function toString() {
		return 'debugger';
	}
}
