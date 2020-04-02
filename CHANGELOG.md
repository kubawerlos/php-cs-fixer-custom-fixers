# CHANGELOG for PHP CS Fixer: custom fixers

## v2.2.0 - *2020-04-02*
- Feature: DataProviderNameFixer - add options "prefix" and "suffix"

## v2.1.0 - *2020-03-15*
- Add CommentedOutFunctionFixer
- Add NoDuplicatedArrayKeyFixer
- Add NumericLiteralSeparatorFixer

## v2.0.0 - *2020-03-01*
- Drop PHP 7.1 support
- Remove ImplodeCallFixer - use "implode_call"
- Remove NoTwoConsecutiveEmptyLinesFixer - use "no_extra_blank_lines"
- Remove NoUnneededConcatenationFixer - use NoSuperfluousConcatenationFixer
- Remove NoUselessClassCommentFixer - use NoUselessCommentFixer
- Remove NoUselessConstructorCommentFixer - use NoUselessCommentFixer
- Remove NullableParamStyleFixer - use "nullable_type_declaration_for_default_null_value"
- Remove PhpdocVarAnnotationCorrectOrderFixer - use "phpdoc_var_annotation_correct_order"
- Remove SingleLineThrowFixer - use "single_line_throw"

## v1.17.0 - *2019-12-29*
- Update PHP CS Fixer to v2.16
- Add DataProviderStaticFixer
- Add NoSuperfluousConcatenationFixer
- Add PhpdocTypesTrimFixer
- Feature: NoSuperfluousConcatenationFixer - add option "allow_preventing_trailing_spaces"
- Feature: NoSuperfluousConcatenationFixer - handle concatenation of single and double quoted strings together
- Deprecate NoUnneededConcatenationFixer
- Deprecate NullableParamStyleFixer
- Deprecate SingleLineThrowFixer
- Allow symfony/finder 5.0
- Add Windows OS support with AppVeyor

## v1.16.0 - *2019-10-24*
- Add PhpdocOnlyAllowedAnnotationsFixer
- Feature: OperatorLinebreakFixer - handle object operators

## v1.15.0 - *2019-08-19*
- Add CommentSurroundedBySpacesFixer
- Add DataProviderReturnTypeFixer
- Add NoDuplicatedImportsFixer

## v1.14.0 - *2019-07-25*
- Add DataProviderNameFixer
- Add NoUselessSprintfFixer
- Add PhpUnitNoUselessReturnFixer
- Add SingleLineThrowFixer
- Feature: NoCommentedOutCodeFixer - handle class method

## v1.13.0 - *2019-01-11*
- Update PHP CS Fixer to v2.14
- OperatorLinebreakFixer - respect no whitespace around operator
- OperatorLinebreakFixer - support concatenation operator
- Deprecate PhpdocVarAnnotationCorrectOrderFixer

## v1.12.0 - *2018-12-02*
- Add NoCommentedOutCodeFixer
- Add NoUselessCommentFixer
- Add NullableParamStyleFixer
- Deprecate NoUselessClassCommentFixer
- Deprecate NoUselessConstructorCommentFixer
- Feature: OperatorLinebreakFixer - handle ternary operator
- Fix: NoImportFromGlobalNamespaceFixer - class without  namespace
- Fix: NoUselessClassCommentFixer - comment detection
- Fix: TokenRemover - remove last element of file
- Fix: TokenRemover - remove item in line after code
- Fix: NoImportFromGlobalNamespaceFixer - constant named the same as global imported class

## v1.11.0 - *2018-10-14*
- Add PhpdocParamOrderFixer
- Add InternalClassCasingFixer
- Add SingleSpaceAfterStatementFixer
- Add SingleSpaceBeforeStatementFixer
- Add OperatorLinebreakFixer
- Add MultilineCommentOpeningClosingAloneFixer

## v1.10.0 - *2018-09-19*
- Add NoUnneededConcatenationFixer
- Add PhpdocNoSuperfluousParamFixer
- Deprecate ImplodeCallFixer
- Deprecate NoTwoConsecutiveEmptyLinesFixer

## v1.9.0 - *2018-08-10*
- Add NoNullableBooleanTypeFixer

## v1.8.0 - *2018-08-09*
- Add PhpdocSelfAccessorFixer

## v1.7.0 - *2018-08-06*
- Add NoReferenceInFunctionDefinitionFixer
- Add NoImportFromGlobalNamespaceFixer

## v1.6.0 - *2018-07-22*
- Add ImplodeCallFixer
- Add PhpdocSingleLineVarFixer

## v1.5.0 - *2018-06-26*
- Add NoUselessDoctrineRepositoryCommentFixer

## v1.4.0 - *2018-06-09*
- Add NoDoctrineMigrationsGeneratedCommentFixer

## v1.3.0 - *2018-06-05*
- Add PhpdocVarAnnotationCorrectOrderFixer
- Remove @var without type at the beginning in PhpdocNoIncorrectVarAnnotationFixer

## v1.2.0 - *2018-06-03*
- Add PhpdocNoIncorrectVarAnnotationFixer

## v1.1.0 - *2018-06-02*
- Update PHP CS Fixer to v2.12
- Add NoUselessConstructorCommentFixer
- Add PhpdocParamTypeFixer
- Feature: code coverage
- Feature: create Travis stages
- Feature: verify correctness for PHP CS Fixer (without smote tests)
- Fix: false positive class comment

## v1.0.0 - *2018-05-21*
- Add NoLeadingSlashInGlobalNamespaceFixer
- Add NoPhpStormGeneratedCommentFixer
- Add NoTwoConsecutiveEmptyLinesFixer
- Add NoUselessClassCommentFixer
