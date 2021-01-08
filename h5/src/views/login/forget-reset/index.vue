<template>
  <md-field-group class="foget_view">
    <md-field
      v-model="password"
      icon="lock"
      :is-error="isErrow"
      placeholder="请输入新密码" />

    <md-field
      v-model="passwordRepeat"
      type="password"
      icon="lock"
      :is-error="isErrow"
      placeholder="请再次输入密码" />
    <div class="red" v-show="isErrow">两次密码输入不一致</div>

    <div class="foget_submit">
      <van-button size="large" type="danger" @click="submitCode">重置</van-button>
    </div>
  </md-field-group>
</template>

<script>
import field from '@/components/field'
import fieldGroup from '@/components/field-group'

export default {
  components: {
    MdField: field,
    MdFieldGroup: fieldGroup
  },

  data() {
    return {
      isErrow: true,
      password: '',
      passwordRepeat: ''
    }
  },

  methods: {
    submitCode() {}
  }
}
</script>

<style lang="scss" scoped>
div.foget_view {
  background-color: #fff;
  padding-top: 30px;
}

div.foget_submit {
  padding-top: 30px;
  padding-bottom: 20px;
}
</style>
