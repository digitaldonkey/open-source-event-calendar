<?php

/* datepicker_link.twig */
class __TwigTemplate_4932ea4178e92f66ddbdb1feb9ec1496773dcb031265fc870a3e981abc68ca2a extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<a
\tclass=\"ai1ec-minical-trigger ai1ec-btn ai1ec-btn-sm ai1ec-btn-default
    ai1ec-tooltip-trigger\"
\t";
        // line 4
        $context['_parent'] = (array) $context;
        $context['_seq'] = twig_ensure_traversable((isset($context["attributes"]) ? $context["attributes"] : null));
        foreach ($context['_seq'] as $context["attribute"] => $context["value"]) {
            // line 5
            echo "\t\t";
            echo twig_escape_filter($this->env, (isset($context["attribute"]) ? $context["attribute"] : null), "html", null, true);
            echo "=\"";
            echo twig_escape_filter($this->env, (isset($context["value"]) ? $context["value"] : null), "html", null, true);
            echo "\"
\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['attribute'], $context['value'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 7
        echo "\t";
        echo (isset($context["data_type"]) ? $context["data_type"] : null);
        echo "
\ttitle=\"";
        // line 8
        echo twig_escape_filter($this->env, (isset($context["text_date"]) ? $context["text_date"] : null), "html", null, true);
        echo "\"
\t>
\t<i class=\"ai1ec-fa ai1ec-fa-calendar-o ai1ec-fa-fw ai1ec-fa-lg\"></i>
  <span class=\"ai1ec-calendar-title\">";
        // line 11
        echo twig_escape_filter($this->env, (isset($context["title"]) ? $context["title"] : null), "html", null, true);
        echo "</span>
  <span class=\"ai1ec-calendar-title-short\">";
        // line 12
        echo twig_escape_filter($this->env, (isset($context["title_short"]) ? $context["title_short"] : null), "html", null, true);
        echo "</span>
</a>
";
    }

    public function getTemplateName()
    {
        return "datepicker_link.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  100 => 31,  88 => 26,  298 => 115,  288 => 112,  281 => 111,  279 => 110,  263 => 103,  253 => 98,  244 => 94,  239 => 92,  228 => 87,  226 => 86,  220 => 83,  200 => 76,  195 => 73,  193 => 72,  177 => 61,  121 => 37,  92 => 25,  44 => 8,  306 => 118,  296 => 114,  294 => 80,  280 => 74,  274 => 71,  271 => 70,  264 => 67,  260 => 66,  225 => 61,  209 => 79,  192 => 49,  188 => 48,  178 => 43,  175 => 60,  152 => 33,  148 => 32,  144 => 46,  141 => 45,  102 => 26,  90 => 27,  78 => 19,  42 => 11,  39 => 7,  217 => 82,  211 => 80,  194 => 50,  183 => 72,  179 => 70,  168 => 64,  161 => 61,  143 => 45,  116 => 35,  111 => 35,  93 => 27,  86 => 21,  81 => 24,  59 => 15,  291 => 79,  283 => 105,  276 => 72,  267 => 102,  254 => 97,  248 => 94,  245 => 93,  233 => 88,  231 => 88,  224 => 84,  221 => 84,  207 => 56,  204 => 77,  202 => 53,  197 => 51,  189 => 65,  180 => 65,  176 => 60,  170 => 57,  153 => 55,  140 => 47,  137 => 46,  132 => 40,  122 => 38,  112 => 32,  99 => 27,  95 => 26,  91 => 24,  83 => 23,  79 => 24,  58 => 14,  50 => 11,  45 => 11,  29 => 9,  25 => 4,  164 => 37,  156 => 77,  139 => 44,  131 => 42,  127 => 39,  123 => 29,  114 => 33,  104 => 52,  96 => 24,  77 => 23,  74 => 19,  68 => 16,  60 => 28,  27 => 6,  66 => 16,  598 => 338,  590 => 332,  582 => 327,  573 => 320,  571 => 318,  565 => 314,  563 => 313,  560 => 312,  554 => 309,  551 => 308,  549 => 307,  546 => 306,  539 => 302,  534 => 300,  530 => 299,  527 => 298,  524 => 297,  521 => 295,  512 => 289,  503 => 283,  497 => 280,  488 => 274,  479 => 268,  474 => 265,  471 => 264,  464 => 258,  458 => 256,  451 => 253,  449 => 252,  443 => 248,  437 => 246,  430 => 243,  428 => 242,  423 => 239,  415 => 233,  407 => 228,  393 => 217,  387 => 213,  380 => 207,  374 => 205,  367 => 202,  365 => 201,  359 => 197,  353 => 195,  346 => 192,  344 => 191,  339 => 188,  333 => 183,  327 => 181,  320 => 178,  318 => 177,  313 => 94,  308 => 91,  302 => 116,  299 => 88,  293 => 163,  287 => 106,  284 => 75,  282 => 158,  278 => 156,  275 => 154,  269 => 106,  266 => 68,  258 => 101,  255 => 142,  251 => 140,  249 => 139,  242 => 63,  236 => 89,  222 => 125,  216 => 82,  210 => 78,  203 => 78,  187 => 104,  174 => 59,  166 => 57,  159 => 52,  155 => 50,  145 => 49,  138 => 74,  134 => 41,  119 => 62,  107 => 29,  101 => 27,  97 => 28,  80 => 39,  71 => 21,  62 => 15,  49 => 12,  35 => 7,  30 => 6,  63 => 18,  57 => 14,  54 => 12,  43 => 17,  31 => 6,  24 => 4,  21 => 2,  82 => 20,  73 => 22,  70 => 24,  64 => 15,  55 => 14,  52 => 15,  48 => 14,  46 => 18,  41 => 14,  37 => 9,  32 => 6,  22 => 2,  265 => 123,  259 => 100,  252 => 117,  250 => 97,  247 => 115,  241 => 112,  234 => 89,  232 => 108,  229 => 129,  227 => 62,  219 => 82,  213 => 81,  205 => 93,  201 => 114,  199 => 74,  196 => 77,  190 => 86,  186 => 84,  184 => 67,  181 => 101,  173 => 67,  169 => 39,  167 => 76,  162 => 54,  160 => 72,  157 => 52,  151 => 50,  149 => 47,  142 => 63,  135 => 42,  130 => 40,  128 => 38,  125 => 38,  117 => 34,  113 => 58,  108 => 33,  106 => 28,  103 => 28,  94 => 29,  89 => 26,  87 => 24,  84 => 25,  76 => 38,  72 => 27,  67 => 19,  65 => 17,  61 => 17,  56 => 16,  53 => 13,  51 => 13,  40 => 10,  34 => 10,  28 => 5,  26 => 4,  36 => 9,  23 => 3,  19 => 1,);
    }
}