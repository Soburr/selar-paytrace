<script setup>
import { ref } from 'vue'

const token = ref('')
const loading = ref(false)
const error = ref(null)
const result = ref(null)

const stages = [
  { key: 'payment_received', label: 'Payment received' },
  { key: 'verified', label: 'Verified' },
  { key: 'product_access_confirmed', label: 'Access confirmed' },
]

function stageIndex(status) {
  return stages.findIndex((s) => s.key === status)
}

async function checkStatus() {
  if (!token.value.trim()) return

  loading.value = true
  error.value = null
  result.value = null

  try {
    const response = await fetch(`/track/${encodeURIComponent(token.value.trim())}`)

    if (response.status === 404) {
      error.value = "We couldn't find a payment with that tracking code. Double-check it and try again."
      return
    }

    if (response.status === 429) {
      error.value = 'Too many checks in a short time. Wait a minute and try again.'
      return
    }

    if (!response.ok) {
      error.value = 'Something went wrong on our end. Try again shortly.'
      return
    }

    result.value = await response.json()
  } catch (e) {
    error.value = 'Could not reach the tracker. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

function formatAmount(amount, currency) {
  return new Intl.NumberFormat('en-NG', { style: 'currency', currency }).format(amount)
}

function formatTime(isoString) {
  if (!isoString) return null
  return new Date(isoString).toLocaleString('en-NG', {
    day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit',
  })
}
</script>

<template>
  <div class="page">
    <div class="card">
      <div class="card-header">
        <span class="eyebrow">Payment tracker</span>
        <h1>Where's my payment?</h1>
        <p class="sub">Enter the tracking code from your receipt to see its current status.</p>
      </div>

      <form class="lookup" @submit.prevent="checkStatus">
        <input
          v-model="token"
          type="text"
          placeholder="e.g. 22076282d5a1ea43eb3e95ce74f22d38"
          autocomplete="off"
          spellcheck="false"
        />
        <button type="submit" :disabled="loading">
          {{ loading ? 'Checking…' : 'Track' }}
        </button>
      </form>

      <p v-if="error" class="error">{{ error }}</p>

      <div v-if="result" class="result">
        <div class="amount-row">
          <span class="amount">{{ formatAmount(result.amount, result.currency) }}</span>
          <span class="ref">{{ result.tracking_token }}</span>
        </div>

        <div class="rail">
          <div
            v-for="(stage, i) in stages"
            :key="stage.key"
            class="rail-step"
            :class="{ done: i <= stageIndex(result.status), current: i === stageIndex(result.status) }"
          >
            <div class="dot"></div>
            <div class="rail-content">
              <span class="rail-label">{{ stage.label }}</span>
              <span class="rail-time">
                {{ formatTime(result.timeline[stage.key + '_at']) }}
              </span>
            </div>
          </div>
        </div>

        <p class="status-note" :class="{ delayed: result.is_delayed }">{{ result.status_label }}</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  /* --brand: swap this one value for Selar's confirmed hex once you
     have it from DevTools - nothing else needs to change. */
  --brand: #7C3AED;
  --brand-soft: rgba(124, 58, 237, 0.12);
  background: #FAFAFA;
  font-family: 'Inter', -apple-system, sans-serif;
}

.card {
  width: 100%;
  max-width: 440px;
  background: #FFFFFF;
  border: 1px solid #ECECEC;
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
}

.eyebrow {
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--brand);
}

h1 {
  font-size: 26px;
  font-weight: 700;
  margin: 8px 0 4px;
  color: #16181D;
  letter-spacing: -0.01em;
}

.sub {
  font-size: 14px;
  color: #6B7280;
  margin: 0 0 24px;
  line-height: 1.4;
}

.lookup {
  display: flex;
  gap: 8px;
}

.lookup input {
  flex: 1;
  padding: 12px 14px;
  border: 1px solid #E5E7EB;
  border-radius: 10px;
  font-family: 'JetBrains Mono', ui-monospace, monospace;
  font-size: 13px;
  background: #fff;
  color: #16181D;
}

.lookup input:focus {
  outline: none;
  border-color: var(--brand);
  box-shadow: 0 0 0 3px var(--brand-soft);
}

.lookup button {
  padding: 12px 20px;
  border: none;
  border-radius: 10px;
  background: var(--brand);
  color: #fff;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
}

.lookup button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.error {
  margin-top: 16px;
  padding: 12px 14px;
  background: #FEF2F2;
  border-radius: 8px;
  color: #B42318;
  font-size: 13px;
  line-height: 1.4;
}

.result {
  margin-top: 28px;
  padding-top: 24px;
  border-top: 1px solid #F0F0F0;
}

.amount-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 24px;
}

.amount {
  font-size: 24px;
  font-weight: 700;
  color: #16181D;
}

.ref {
  font-family: 'JetBrains Mono', ui-monospace, monospace;
  font-size: 11px;
  color: #9CA3AF;
}

.rail {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.rail-step {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 8px 0;
  opacity: 0.4;
}

.rail-step.done {
  opacity: 1;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #E5E7EB;
  margin-top: 4px;
  flex-shrink: 0;
}

.rail-step.done .dot {
  background: var(--brand);
}

.rail-step.current .dot {
  box-shadow: 0 0 0 4px var(--brand-soft);
}

.rail-content {
  display: flex;
  flex-direction: column;
}

.rail-label {
  font-size: 14px;
  font-weight: 600;
  color: #16181D;
}

.rail-time {
  font-size: 12px;
  color: #9CA3AF;
  min-height: 16px;
}

.status-note {
  margin-top: 20px;
  font-size: 13px;
  color: #6B7280;
  text-align: center;
}

.status-note.delayed {
  color: #B45309;
  background: #FFFBEB;
  padding: 10px 12px;
  border-radius: 8px;
  font-weight: 500;
}
</style>