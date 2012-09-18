<?php
error_reporting(E_ALL|E_STRICT);

spl_autoload_register(array('AST', 'load'));

class AST {
	protected $tree;
	protected $scope;

	protected $rootScope;
	protected $rootLabelScope;

	protected $labelScope;

	public static $finalize = false;

	protected $reports = array();

	protected $visitScope = null;

	protected $consts = array();

	public static $options = array(
		'crush-bool' => true,
		'mangle' => true,
		'unsafe' => false,
		'strip-console' => false,
		'timer' => false,
		'beautify' => false,
		'no-copyright' => false,
		'strip-debug' => false,
		'no-inlining' => false,
		'unicode-ws' => false,
		'toplevel' => false,
		'squeeze' => false,
		'profile' => false,
		'ascii' => false
	);

	public static function load($class) {
		$root = dirname(__FILE__) . '/';
		$options = array(strtolower(preg_replace('~([a-zA-Z])(?=[A-Z][a-z])~', '$1_', $class)), strtolower($class));

		foreach($options as $file) {
			foreach(array('', 'nodes/') as $dir) {
				if (is_file($root . $dir . $file . '.php')) {
					require $root . $dir . $file . '.php';
					return;
				}
			}
		}
	}

	public function __construct(JSNode $root, array $consts = array()) {
		$this->rootScope = $this->enter();
		$this->rootLabelScope = $this->labelScope = new Scope($this, null, true);

		if (AST::$options['toplevel']) {
			// sneaky trick for mangling toplevel, just pretend we're already in a scope
			$this->enter();
		}

		foreach($consts as $name => $const) {
			if (!($const instanceof Expression)) {
				throw new Exception;
			}

			$this->consts[$name] = $const;
		}

		$this->tree = $this->generate($root);
		$this->leave();

		$this->secondVisit = false;
	}

	public function squeeze() {
		$oldBeautify = self::$options['beautify'];
		self::$options['beautify'] = false;

		$this->tree->collectStatistics($this);
		$this->secondVisit = true;
		// visit twice
		$this->tree = $this->tree->visit($this);
		$this->tree = $this->tree->visit($this);

		if (AST::$options['mangle']) {
			$this->rootScope->optimizeList();

			$this->rootScope->optimize();
			$this->rootLabelScope->optimize();
		}

		self::$options['beautify'] = $oldBeautify;
		self::$options['squeeze'] = false;

		self::$finalize = true;

		return $this;
	}

	public function visitScope(Scope $scope = null) {
		if ($scope !== null) {
			$this->visitScope = $scope;
		}

		return $this->visitScope;
	}

	public function hasStats() {
		return $this->secondVisit;
	}

	public function countLetters(&$letters) {
		$this->tree->countLetters($letters);
	}

	public function toString() {
		// we still need stats…
		if (!$this->secondVisit) {
			$this->tree->collectStatistics($this);
		}

		// strip out final semicolons
		return str_replace("\0", '', $this->tree->asBlock()->toString(true));
	}

	public function report($rep = null) {
		if ($rep !== null) {
			$this->reports[] = $rep;
		}

		return $this->reports;
	}

	public function tree() {
		return $this->tree;
	}

	protected function enter() {
	//	$this->labelScope = new Scope($this, $this->labelScope, true);

		return $this->scope = new Scope($this, $this->scope);
	}

	protected function leave() {
	//	$this->labelScope = $this->labelScope->parent();

		return $this->scope = $this->scope->parent();
	}

	public function generate($n = null, $dotBase = true, $asArray = true, $inAssign = false) {
		if ($n === null) {
			return new VoidExpression(new Number(0));
		}

		if (!($n instanceof JSNode)) {
			return $n;
		}

		switch($n->type) {
		case JS_SCRIPT:
			foreach($n->context->funDecls as $fun) {
				$this->scope->find($fun->name, true);
			}

			foreach($n->context->varDecls as $var) {
				$this->scope->find($var, true);
			}

			return new ScriptNode($this->nodeList($n->nodes), $this->scope);
		case JS_BLOCK:
			$nl = $this->nodeList($n->nodes);

			if (count($nl) === 1) {
				return $nl[0];
			}

			return new BlockStatement($nl);
		case JS_FOR_IN:
			$it = $this->generate($n->varDecl ? $n->varDecl : $n->iterator);

			return new ForInNode(
				$n->varDecl ? $it[0] : $it,
				$this->generate($n->object),
				$this->block($n->body)
			);
		case KEYWORD_RETURN:
			return new ReturnNode(
				$this->generate($n->value)
			);
		case KEYWORD_FOR:
			return new ForNode(
				$this->generate($n->setup, true, false),
				$this->generate($n->condition),
				$this->generate($n->update),
				$this->block($n->body)
			);
		case OP_SEMICOLON:
			if ($n->expression) {
				return $this->generate($n->expression);
			}

			return new VoidExpression(new Number(0));
		case JS_GROUP:
			return $this->generate($n->nodes[0]);
		case JS_CALL:
			return new CallExpression(
				$this->generate($n->nodes[0]),
				$this->generate($n->nodes[1])
			);
		case JS_LABEL:
			$this->labelScope = new Scope($this, $this->labelScope, true);
			$result = new LabelNode($this->labelScope->find($n->label, true), $this->generate($n->statement));
			$this->labelScope = $this->labelScope->parent();

			return $result;
		case KEYWORD_FUNCTION:
			$ident = $n->name === null ? null : new IdentifierExpression($this->scope->find($n->name, true));

			// because of IE, include the name in the parent scope
			$this->enter();

			$f = new FunctionNode(
				$ident,
				$this->identifierList($n->params),
				$this->generate($n->body),
				$n->functionForm
			);

			$this->leave();

			if ($n->functionForm === EXPRESSED_FORM) {
				return new FunctionExpression($f);
			} elseif ($n->functionForm === STATEMENT_FORM) {
				return new VarNode($ident, new FunctionExpression($f));
			}


			return $f;
		case KEYWORD_VAR:
			$l = array();
			foreach($n->nodes as $x) {
				$l[] = new VarNode(
					new IdentifierExpression($this->scope->find($x->name, true)),
					$this->generate($x->initializer)
				);
			}

			if (!$asArray) {
				return new VarDeclarationsNode($l);
			}

			return $l;
		case OP_DOT:
			return new DotExpression(
				$this->generate($n->nodes[0], $dotBase, true, $inAssign),
				$this->generate($n->nodes[1], false)
			);
		case KEYWORD_TYPEOF:
			return new TypeofExpression(
				$this->generate($n->nodes[0])
			);
		case KEYWORD_DELETE:
			return new DeleteExpression(
				$this->generate($n->nodes[0])
			);
		case KEYWORD_THROW:
			return new ThrowNode(
				$this->generate($n->exception)
			);
		case KEYWORD_INSTANCEOF:
			return new InstanceofExpression(
				$this->generate($n->nodes[0]),
				$this->generate($n->nodes[1])
			);
		case TOKEN_IDENTIFIER:
			if ($dotBase) {
				$expr = new IdentifierExpression($this->scope->find($n->value));

				if (!$inAssign && isset($this->consts[$n->value]) && !$expr->declared()) {
					return $this->consts[$n->value];
				}

				return $expr;
			} else {
				return new Identifier(null, $n->value);
			}
		case KEYWORD_IF:
			return new IfNode(
				$this->generate($n->condition),
				$this->block($n->thenPart),
				$n->elsePart ? $this->block($n->elsePart) : null
			);
		case KEYWORD_VOID:
			return new VoidExpression(
				$this->generate($n->nodes[0])
			);
		case TOKEN_NUMBER:
			return new Number($n->value);
		case KEYWORD_TRUE:
		case KEYWORD_FALSE:
			return new Boolean($n->type === KEYWORD_TRUE ? true : false);
		case TOKEN_REGEXP:
			return new RegExp($n->value);
		case TOKEN_STRING:
			return new String($n->value);
		case KEYWORD_NULL:
			return new Nil();
		case KEYWORD_WHILE:
			return new WhileNode($this->generate($n->condition), $this->block($n->body));
		case KEYWORD_DO:
			return new DoWhileNode($this->generate($n->condition), $this->block($n->body));
		case OP_OR:
			return new OrExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_AND:
			return new AndExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_BITWISE_XOR:
			return new BitwiseXorExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_BITWISE_OR:
			return new BitwiseOrExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_BITWISE_AND:
			return new BitwiseAndExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_BITWISE_NOT:
			return new BitwiseNotExpression($this->generate($n->nodes[0]));
		case OP_EQ:
		case OP_STRICT_EQ:
			return new EqualExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]), $n->type === OP_STRICT_EQ);
		case OP_NE:
		case OP_STRICT_NE:
			return new NotEqualExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]), $n->type === OP_STRICT_NE);
		case OP_LT:
		case OP_LE:
		case OP_GE:
		case OP_GT:
			return new ComparisonExpression($n->type, $this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_MUL:
			return new MulExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_DIV:
			return new DivExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_MOD:
			return new ModExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_PLUS:
			return new PlusExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_MINUS:
			return new MinusExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_LSH:
		case OP_RSH:
		case OP_URSH:
			return new BitwiseShiftExpression($n->type, $this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_UNARY_PLUS:
			return new UnaryPlusExpression($this->generate($n->nodes[0]));
		case OP_UNARY_MINUS:
			return new UnaryMinusExpression($this->generate($n->nodes[0]));
		case OP_INCREMENT:
			return new IncrementExpression($this->generate($n->nodes[0]), !!$n->postfix);
		case OP_DECREMENT:
			return new DecrementExpression($this->generate($n->nodes[0]), !!$n->postfix);
		case OP_NOT:
			return new NotExpression($this->generate($n->nodes[0]));
		case OP_HOOK:
			return new HookExpression(
				$this->generate($n->nodes[0]),
				$this->generate($n->nodes[1]),
				$this->generate($n->nodes[2])
			);
		case KEYWORD_IN:
			return new InExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case JS_INDEX:
			return new IndexExpression($this->generate($n->nodes[0], $dotBase, $asArray, $inAssign), $this->generate($n->nodes[1]));
		case JS_ARRAY_INIT:
			return new ArrayExpression($this->nodeList($n->nodes));
		case KEYWORD_THIS:
			return new This();
		case TOKEN_UNDEFINED:
			return new Undefined();
		case KEYWORD_NEW:
		case JS_NEW_WITH_ARGS:
			return new NewExpression(
				$this->generate($n->nodes[0]),

				$this->nodeList(isset($n->nodes[1]) ? $n->nodes[1]->nodes : array())
			);
		case OP_ASSIGN:
			return new AssignExpression(
				$n->value,

				$this->generate($n->nodes[0], true, true, true),
				$this->generate($n->nodes[1])
			);
		case JS_OBJECT_INIT:
			$base = $this;
			return new ObjectExpression(array_map(function ($x) use($base) {
				return new Property($base->generate($x->nodes[0], false), $base->generate($x->nodes[1]));
			}, $n->nodes));
		case KEYWORD_BREAK:
			return new BreakNode($n->label ? $this->labelScope->find($n->label) : null);
		case KEYWORD_CONTINUE:
			return new ContinueNode($n->label ? $this->labelScope->find($n->label) : null);
		case JS_LIST:
			return $this->nodeList($n->nodes);
		case OP_COMMA:
			return new CommaExpression($this->nodeList($n->nodes));
		case KEYWORD_TRY:
			return new TryNode(
				$this->block($n->tryBlock),
				$n->catchClause ? $this->generate($n->catchClause) : null,
				$n->finallyBlock ? $this->block($n->finallyBlock) : null
			);
		case KEYWORD_CATCH:
			return new CatchNode(
				$this->scope->find($n->varName, true),
				$this->block($n->block)
			);
		case KEYWORD_SWITCH:
			return new SwitchNode(
				$this->generate($n->discriminant),
				$this->nodeList($n->cases)
			);
		case KEYWORD_CASE:
			return new CaseNode(
				$this->generate($n->caseLabel),
				$this->generate($n->statements)
			);
		case KEYWORD_DEFAULT:
			return new DefaultCaseNode(
				$this->generate($n->statements)
			);
		case KEYWORD_DEBUGGER:
			return new DebuggerNode();
		case KEYWORD_WITH:
			return new WithNode(
				$this->generate($n->object),
				$this->block($n->body)
			);
		}

		throw new RuntimeException('Unknown handler for ' . $n->type);
	}

	protected function nodeList(array $l) {
		$n = array();

		foreach($l as $x) {
			$q = $this->generate($x);
			foreach(is_array($q) ? $q : array($q) as $q) {
				if ($q) {
					$n[] = $q;
				}
			}
		}

		return $n;
	}

	protected function identifierList(array $list) {
		$n = array();

		foreach($list as $x) {
			$n[] = new IdentifierExpression($this->scope->find($x, true));
		}

		return $n;
	}

	protected function block(JSNode $n) {
		return new BlockStatement($this->nodeList($n->type !== JS_BLOCK ? array($n) : $n->nodes));
	}

	public static function bestOption($options) {
		if (!is_array($options)) {
			return $options;
		}

		$minLength = null;
		$min = null;

		foreach($options as $option) {
			$length = strlen($option);
			if ($length < $minLength || $minLength === null) {
				$minLength = $length;
				$min = $option;
			}
		}

		return $min;
	}

}




