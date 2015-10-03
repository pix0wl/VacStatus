'use strict';

let routes = (
	<Router history={ createBrowserHistory() }>
		<Route path="/" component={ App }>
			<IndexRoute component={ Home } />
			<Route path="news" component={ News }>
				<Route path=":page" component={ News }/>
			</Route>
			<Route path="list" component={ ListPortal }/>
			<Route path="list/*" component={ List }/>
			<Route path="u/:steamId" component={ Profile }/>
			<Route path="search/:searchId" component={ Search }/>
			<Route path="donate" component={ Home }/>
			<Route path="privacy" component={ Privacy }/>
		</Route>
	</Router>
);

React.render(routes, document.getElementById('app'));