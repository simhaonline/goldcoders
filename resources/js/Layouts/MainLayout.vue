<template>
  <v-app>
    <!-- <right-side-bar/> -->
    <!-- <app-nav-bar :title="title" with-right/> -->
    <app-nav-bar :title="title"/>
    <left-side-bar/>
    <v-content transition="slide-x-transition" fluid pa-0 ma-0>
      <slot/>
    </v-content>
    <app-footer/>
  </v-app>
</template>

<script>
import AppFooter from "../partials/AppFooter.vue";
import AppNavBar from "../partials/AppNavBar.vue";
import LeftSideBar from "../partials/LeftSideBar.vue";
// import RightSideBar from "../partials/RightSideBar.vue";

export default {
  components: {
    AppFooter,
    AppNavBar,
    LeftSideBar,
    // RightSideBar
  },
  props: {
    title: String
  },
  mounted() {
    this.updatePageTitle(this.title);
  },
  methods: {
    updatePageTitle(title) {
      document.title = title
        ? `${title} | ${this.$page.app.name}`
        : this.$page.app.name;
    }
  },
  watch: {
    title(title) {
      this.updatePageTitle(title);
    }
  }
};
</script>
