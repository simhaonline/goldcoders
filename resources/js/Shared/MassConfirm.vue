<template>
  <v-dialog v-model="dialog" width="500">
    <v-card>
      <v-card-title class="headline error" dark>
        <span class="white--text">{{ title }}</span>
      </v-card-title>

      <v-card-text>{{ message }}</v-card-text>

      <v-divider />

      <v-card-actions>
        <v-btn text color="secondary" @click="dialog = false">
          <v-icon left>arrow_left</v-icon>Back
        </v-btn>
        <v-spacer />
        <v-btn color="primary" text @click="confirm()">{{ action }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
export default {
  props: {
    callback: {
      type: Function,
      required: true,
    },
    message: {
      type: String,
      default: function() {
        return "Halt! This Action is Destructive and Irreversible Only Proceed If You Think Deleting All The Selected Record Has No Impact On Your Data Record.";
      },
    },
    title: {
      type: String,
      default: function() {
        return "You are Deleting The Selected Items!";
      },
    },
    action: {
      type: String,
      default: function() {
        return "Yes , I Understand";
      },
    },
  },
  data: () => ({
    dialog: false,
    params: null,
  }),
  mounted() {
    // eslint-disable-next-line no-undef
    Bus.$on("open-mass-confirm-dialog", params => {
      this.dialog = true;
      this.params = params;
    });
  },
  methods: {
    closeDialog() {
      this.dialog = false;
    },
    confirm() {
      if (this.callback) {
        if (this.params != undefined) {
          this.dialog = false;

          this.callback(this.params);
        } else {
          this.dialog = false;

          this.callback();
        }
      }
    },
  },
};
</script>

<style>
</style>
