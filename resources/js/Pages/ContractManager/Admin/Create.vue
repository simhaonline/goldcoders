<template>
  <main-layout :title="name">
    <v-container grid-list-md>
      <v-layout row my-4>
        <inertia-link
          class="headline font-weight-thin inertia-link"
          :href="route('admin_file_manager')"
        >{{ name }}</inertia-link>
        <span class="headline font-weight-thin mx-1">/</span>
        <span class="headline font-weight-thin">Create</span>
      </v-layout>

      <v-layout row>
        <v-flex md6>
          <v-card>
            <v-container class="pa-2" fluid grid-list-md>
              <v-layout column>
                <span class="header grey--text ml-3 mt-3">General</span>

                <v-flex px-5>
                  <v-text-field
                    autofocus
                    v-model="form.title"
                    class="primary--text"
                    label="Title"
                    prepend-icon="assessment"
                    :error-messages="$page.errors.title"
                  />
                  <v-switch v-model="form.active" label="Active ?"></v-switch>
                </v-flex>
              </v-layout>
            </v-container>
          </v-card>
        </v-flex>
        <v-flex md6>
          <v-card>
            <v-container class="pa-2" fluid grid-list-md>
              <v-layout column>
                <span class="header grey--text ml-3 mt-3">Details</span>

                <v-flex px-5>
                  <v-flex md11 offset-md1>
                    <v-file-input
                      v-model="form.images"
                      placeholder="Upload your documents"
                      label="Documents"
                      multiple
                      prepend-icon="mdi-camera"
                      :show-size="1000"
                      counter
                      accept="image/*"
                    >
                      <template v-slot:selection="{ text }">
                        <v-chip small label color="primary">{{ text }}</v-chip>
                      </template>
                    </v-file-input>

                    <v-card v-if="images.length > 0">
                      <v-container fluid>
                        <v-row>
                          <v-col
                            v-for="(image,key) in images"
                            :key="key"
                            class="d-flex child-flex"
                            cols="4"
                          >
                            <v-card flat tile class="d-flex">
                              <v-img :src="image" aspect-ratio="1" class="grey lighten-2">
                                <template v-slot:placeholder>
                                  <v-row class="fill-height ma-0" align="center" justify="center">
                                    <v-progress-circular indeterminate color="grey lighten-5"></v-progress-circular>
                                  </v-row>
                                </template>
                              </v-img>
                            </v-card>
                          </v-col>
                        </v-row>
                      </v-container>
                    </v-card>
                  </v-flex>
                </v-flex>
              </v-layout>
            </v-container>

            <v-layout align-center justify-center row fill-height py-3>
              <v-btn
                class="ma-2"
                depressed
                color="indigo darken-4"
                :loading="form.busy"
                :disabled="errors.any() || form.busy"
                @click.native="submit()"
              >
                <span class="white--text">Create {{ name }}</span>
              </v-btn>
            </v-layout>
          </v-card>
        </v-flex>
        <pre>{{ $data }}</pre>
      </v-layout>
    </v-container>
  </main-layout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout";
import AdminDashPanel from "@/Shared/AdminDashPanel";
import objectToFormData from "object-to-formdata";

export default {
  components: {
    MainLayout,
    AdminDashPanel
  },
  created() {},
  data() {
    return {
      dialog: false,
      name: "Admin File Manager",
      images: [],
      form: {
        title: null,
        active: true,
        busy: false,
        images: null
      }
    };
  },
  methods: {
    submit() {
      let self = this;
      self.form.busy = true;
      self.$inertia
        .post(self.route("admin_file_manager.store").url(), objectToFormData(self.form), {
          replace: true,
          preserveState: true
        })
        .then(() => (self.form.busy = false));
    }
  },
  watch: {
    "form.images": {
      handler: function(val, oldVal) {
        let self = this;
        self.images = [];
        if (val) {
          var filesAmount = val.length;
          for (let i = 0; i < filesAmount; i++) {
            var reader = new FileReader();

            reader.onload = function(event) {
              self.images.push(event.target.result);
            };
            reader.readAsDataURL(val[i]);
          }
        }
      },
      deep: true
    }
  }
};
</script>

<style scoped>
.img {
  max-width: 100%;
  max-height: 100%;
  height: auto;
  width: auto;
}
.card-action {
  background-color: #f1f5f8;
}
.inertia-link {
  text-decoration: none;
}
</style>