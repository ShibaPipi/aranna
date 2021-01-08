<template>
  <div class="user_information">
    <van-cell-group>
      <van-cell title="头像" class="cell_middle">
        <van-uploader :afterRead="avatarAfterRead">
          <div class="user_avatar_upload">
            <img
              :src="avatar"
              alt="你的头像"
              v-if="avatar"
            />
            <van-icon name="camera_full" v-else />
          </div>
        </van-uploader>
      </van-cell>
      <van-cell title="昵称" to="/user/information/setNickname" :value="nickname" isLink />
      <van-cell title="性别" :value="genderText" @click="showSex = true" isLink />
      <van-cell title="密码设置" to="/user/information/setPassword" isLink />
      <van-cell title="手机号" to="/user/information/setMobile" :value="mobile" isLink />
    </van-cell-group>

    <van-button size="large" class="user_quit" @click="logout">退出当前账户</van-button>

    <van-popup v-model="showSex" position="bottom">
      <van-picker
        showToolbar
        :columns="sexColumns"
        title="选择性别"
        @cancel="showSex = false"
        @confirm="onSexConfirm"
      />
    </van-popup>
  </div>
</template>

<script>
import { Button, Picker, Popup, Uploader } from 'vant'
import { removeLocalStorage } from '@/utils/local-storage'
import { authInfo, authLogout } from '@/api/api'

export default {
  components: {
    VanButton: Button,
    VanUploader: Uploader,
    VanPicker: Picker,
    VanPopup: Popup
  },

  data() {
    return {
      sexColumns: [
        {
          values: ['保密', '男', '女'],
          defaultIndex: 0
        }
      ],
      showSex: false,
      avatar: '',
      nickname: '',
      gender: 0,
      mobile: ''
    }
  },

  computed: {
    genderText() {
      const text = ['保密', '男', '女']
      return text[this.gender] || ''
    }
  },

  created() {
    this.getUserInfo()
  },

  methods: {
    avatarAfterRead(file) {
      console.log(file)
    },
    onSexConfirm(value, index) {
      this.showSex = false
    },
    getUserInfo() {
      authInfo().then(res => {
        this.avatar = res.data.data.avatar
        this.nickname = res.data.data.nickname
        this.gender = res.data.data.gender
        this.mobile = res.data.data.mobile
      })
    },
    async logout() {
      await authLogout()
      await removeLocalStorage('Authorization')
      await removeLocalStorage('avatar')
      await removeLocalStorage('nickname')
      await this.$router.push({ name: 'home' })
    }
  }
}
</script>


<style lang="scss" scoped>
.user_information {
  .user_avatar_upload {
    position: relative;
    width: 50px;
    height: 50px;
    border: 1px solid $border-color;

    img {
      max-width: 100%;
      max-height: 100%;
    }

    i {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 20px;
      color: $border-color;
    }
  }

  .user_quit {
    margin-top: 20px;
  }
}
</style>
