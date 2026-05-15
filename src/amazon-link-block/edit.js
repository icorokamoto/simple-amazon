/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, RadioControl, Placeholder } from '@wordpress/components';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
// import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { searchType, asin, keyword } = attributes || {};
	if ( ! attributes ) {
    return <p>Loading...</p>;
  }
	const blockProps = useBlockProps();
	return (
		<div {...blockProps}>
			{ /* 右側の設定サイドバー */ }
			<InspectorControls>
				<PanelBody title={ __( '検索設定', 'simple-amazon' ) }>
					<RadioControl
						label = "検索タイプ"
						selected = { searchType }
						options = {[
							{ label: 'キーワードで検索', value: 'keyword' },
							{ label: 'ASINで検索', value: 'asin'},
						]}
						onChange={ (value) => setAttributes({ searchType: value })}
					/>
				{ searchType === "keyword" ? (
					<TextControl
						label="キーワード"
						value={ keyword }
						onChange={ (val) => setAttributes({ keyword: val }) }
					/>
					) : (
					<TextControl
						label="ASIN"
						value={ asin }
						onChange={ (val) => setAttributes({ asin: val }) }
					/>
					) }
				</PanelBody>
			</InspectorControls>

			{ /* エディタ上の表示 */ }
			<div className="simple-amazon-block-preview">
				{ ( ( searchType === 'keyword' && keyword ) || ( searchType === 'asin' && asin ) ) ? (
          <div style={ { padding: '10px', border: '1px solid #ddd', background: '#f9f9f9' } }>
            <strong>Amazon商品リンク:</strong> { searchType === 'keyword' ? keyword : asin }
          	<span style={ { fontSize: '0.8em', color: '#666', marginLeft: '10px' } }>
              ({ searchType === 'keyword' ? 'キーワード検索' : 'ASIN指定' })
            </span>
          </div>
          ) : (
          <Placeholder 
            icon="cart" 
            label="Amazon商品リンク" 
            instructions="右側の設定パネルからキーワードまたはASINを入力してください。"
          />
        ) }
			</div>
		</div>
	);
}
