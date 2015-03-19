<?php namespace VacStatus\Update;

use VacStatus\Update\MultiProfile;

use Cache;
use Carbon;
use DateTime;
use Auth;
use Session;

use VacStatus\Models\UserList;
use VacStatus\Models\UserListProfile;

use VacStatus\Steam\Steam;

class CustomList
{
	private $userList;
	private $error = null;

	function __construct(UserList $userList)
	{
		if(!isset($userList->id))
		{
			$this->error = "list_invalid"; return;
		}

		if(Auth::check()) 
		{
			$userId = Auth::User()->id;
			$friendsList = Session::get("friendsList");

			if(($userList->privacy == 3 && $userId != $userList->user_id)
				|| ($userList->privacy == 2 && !in_array($userList->user->small_id, $friendsList)))
			{
				$this->error = "list_no_permission"; return;
			}
		} else if($userList->privacy == 2 || $userList->privacy == 3) {
			$this->error = "list_no_permission"; return;
		}

		$this->userList = $userList;
	}

	public function error()
	{
		return is_null($this->error) ? false : ['error' => $this->error];
	}

	public function myList()
	{
		if(Auth::check() && $this->userList->user_id == Auth::user()->id) return true;

		return false;
	}

	public function getCustomList()
	{
		if($this->error()) return $this->error();
		$userList = $this->userList;

		$userListProfiles = UserList::where('user_list.id', $userList->id)
			->leftjoin('user_list_profile as ulp_1', 'ulp_1.user_list_id', '=', 'user_list.id')
			->leftjoin('user_list_profile as ulp_2', 'ulp_2.profile_id', '=', 'ulp_1.profile_id')
			->leftjoin('profile', 'ulp_1.profile_id', '=', 'profile.id')
			->leftjoin('profile_ban', 'profile.id', '=', 'profile_ban.profile_id')
			->leftjoin('users', 'profile.small_id', '=', 'users.small_id')
			->leftjoin('subscription', 'subscription.user_list_id', '=', 'user_list.id')
			->groupBy('profile.id')
			->orderBy('ulp_1.id', 'desc')
			->get([
		      	'ulp_1.profile_name',

				'profile.id',
				'profile.display_name',
				'profile.avatar_thumb',
				'profile.small_id',

				'profile_ban.vac',
				'profile_ban.vac_banned_on',
				'profile_ban.community',
				'profile_ban.trade',

				'users.site_admin',
				'users.donation',
				'users.beta',

				\DB::raw('max(ulp_1.created_at) as created_at'),
				\DB::raw('count(ulp_2.profile_id) as total'),
				\DB::raw('count(distinct subscription.id) as sub_count'),
			]);

		$return = [
			'title' => $userList->title,
			'author' => $userList->user->display_name,
			'my_list' => $this->myList(),
			'privacy' => $userList->privacy,
			'sub_count' => $userListProfiles[0]->sub_count,
			'list' => []
		];

		foreach($userListProfiles as $userListProfile)
		{
			$vacBanDate = new DateTime($userListProfile->vac_banned_on);

			$return['list'][] = [
				'id'			=> $userListProfile->id,
				'display_name'	=> $userListProfile->profile_name?:$userListProfile->display_name,
				'avatar_thumb'	=> $userListProfile->avatar_thumb,
				'small_id'		=> $userListProfile->small_id,
				'steam_64_bit'	=> Steam::to64Bit($userListProfile->small_id),
				'vac'			=> $userListProfile->vac,
				'vac_banned_on'	=> $vacBanDate->format("M j Y"),
				'community'		=> $userListProfile->community,
				'trade'			=> $userListProfile->trade,
				'site_admin'	=> $userListProfile->site_admin?:0,
				'donation'		=> $userListProfile->donation?:0,
				'beta'			=> $userListProfile->beta?:0,
				'times_added'	=> [
					'number' => $userListProfile->total,
					'time' => (new DateTime($userListProfile->created_at))->format("M j Y")
				],
			];
		}

		$multiProfile = new MultiProfile($return['list']);
		$return['list'] = $multiProfile->run();

		return $return;
	}
}