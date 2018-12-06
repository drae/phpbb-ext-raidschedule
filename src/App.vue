<script>
	import SignupTimeline from './components/SignupTimeline.vue'
	import PlayerListing from './components/PlayerListing.vue'
	import RaidHeader from './components/RaidHeader.vue'
	import { EventBus } from './EventBus'

	export default {
		name: 'App',
		data: function () {
			return {
				rid: rid
			}
		},
		created: function () {
//			EventBus.$on('FetchRaidUpdated', this.UpdateRaid)
		},
		components: {
			SignupTimeline,
			PlayerListing,
			RaidHeader
		}
	}
</script>

<template>
	<div>
		<div class="raid-instance">
			<raid-header :rid="rid"></raid-header>
		</div>

		<div class="raid-instance-content">
			<div class="page-content">
				<div class="page-content-inner">

					<div class="raid-signup-timeline">
						<player-listing :rid="rid"></player-listing>
						<signup-timeline :rid="rid"></signup-timeline>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<style lang="scss">
	.raid-signup-timeline {
		align-self: flex-end;
		align-items: stretch;
		display: flex;
		flex-basis: 100%;
		flex-direction: row;
		flex-wrap: wrap;
		margin-bottom: 50px;
		justify-content: flex-start;
	}
</style>
