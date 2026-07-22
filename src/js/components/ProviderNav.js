/**
 * Provider switcher navigation for the IMGVerse React app.
 *
 * @package IMGVerse
 */

import { PROVIDERS } from '../constants/providers';

/**
 * ProviderNav component.
 *
 * @param {Object}   props              Component props.
 * @param {string}   props.provider     Active provider slug.
 * @param {Function} props.onChange     Called with the next provider slug.
 * @return {JSX.Element} Provider nav markup.
 */
export default function ProviderNav( { provider, onChange } ) {
	return (
		<nav className="imgv-provider-nav" aria-label="IMGVerse providers">
			<ul className="imgv-provider-nav__list">
				{ PROVIDERS.map( ( item ) => {
					const isActive = item.id === provider;

					return (
						<li key={ item.id } className="imgv-provider-nav__item">
							<button
								type="button"
								className={
									isActive
										? 'imgv-provider-nav__button is-active'
										: 'imgv-provider-nav__button'
								}
								aria-pressed={ isActive }
								onClick={ () => onChange( item.id ) }
							>
								{ item.label }
							</button>
						</li>
					);
				} ) }
			</ul>
		</nav>
	);
}
