# Storage, security and lifecycle

[简体中文](LIFECYCLE.zh-CN.md) · [Introduction](../README.md)

## Atomic creation

EDGEDICE preserves its raw placeholder. Parsing/rendering never rolls. CommentPost creating collects eligible ranges, validates the count, generates each result with random_int and writes posts.edge_dice in the same INSERT. There is no roll/preview API, client seed or accepted client result. The database and server are trusted.

A nullable post column avoids orphan rows: hard-deleting a post/discussion removes its dice even while the extension is disabled or removed. Installation adds NULL to existing rows without rewriting content or drawing historical results.

## Editing

Normal Flarum permissions still apply. With allowEdit (default true), the saving hook restores the stored ledger before processing content changes; client/model-assigned ledgers are ignored. Unchanged range sequences preserve their results byte-for-byte. Changed sequences reuse identical ranges FIFO, even across positions, and draw unmatched ranges only in the save path. The replacement ledger omits every unused roll. The configured dice limit applies to new posts and changed sequences. Invalid nonempty ledgers reject content edits instead of silently rerolling.

Any changed sequence marks every remaining die edited, including reused results, because removal can select a favorable outcome. Removing all dice leaves only {v:1,rolls:[],edited:true}, preserving provenance without retaining removed results. Later additions remain marked. Rendering this signal is optional (markEdited); hiding the signal does not erase it. Old posts manually edited to add eligible placeholders can roll and are marked edited.

When allowEdit is false, a rolled post must keep its full range sequence; prose and placeholder whitespace can change. Posts without results stay unrolled on edit. The plugin does not block normal whole-post deletion.

Identical ranges are indistinguishable. These rules do not attest to surrounding prose, prevent delete/repost retries, or prove fairness independent of the administrator. Concurrent edits follow the site's normal post-save behavior; no separate edit history or lock is introduced.

## Preview and dormant recovery

New previews show question marks. Editing previews match known ranges FIFO and display only existing results; unmatched ranges remain pending. In strict mode, an invalid changed sequence displays a short edit warning. Inputs and rich-text editing surfaces remain unenhanced.

No browsing or reinstall operation rolls old posts. Disabled-period content may lose custom formatter tags, so rendering reparses ledger-bearing posts in memory. It displays results only if the entire current range sequence matches the ledger; otherwise results remain dormant. Restoring the original sequence recovers them. An explicit subsequent save with editing enabled may reconcile that sequence using the editing policy.

## Disable / remove / uninstall

1. Back up the database before operational changes.
2. Disable Dice normally and rebuild Flarum caches/assets before Composer removal. Old tags fall back to their raw [min, max] source without plugin PHP/frontend code.
3. Migration down intentionally retains posts.edge_dice. Flarum uninstall removes the extension migration record, not its historical results. Embedded masks disappear after rebuilding without Dice.
4. Reinstall and enable: migration up checks for the retained column; rebuild formatter and frontend assets. No reinstall rerolls.

To permanently destroy all historical dice, export post IDs/ledgers, disable/remove the extension, and explicitly DROP edge_dice from the correctly prefixed posts table. This irreversible DBA action is never automatic.

## Revision rollback

The review revision keeps the v1 [min,max,result] tuple format and adds an optional boolean edited. The first beta.1 formatter can still display those results, ignoring the flag. Rolling back code restores the older strict editing policy and older display/settings behavior. Restoring an old database backup would discard subsequent legitimate posts/edits and is not the normal code rollback procedure.

## Compatibility and bounds

- Target: Flarum ^2.0.2 / PHP ^8.3; Flarum 1.x is unsupported.
- Code, quotes and link labels are excluded. No arbitrary expressions or unbounded arithmetic.
- XML parsing uses LIBXML_NONET and rejects DOCTYPE. Result/edited tag attributes are replaced from the ledger. Admin colors accept only hex/empty; labels are bounded plain text. Priority, numbers, booleans and icon structures are validated server-side.
- Font icons accept one known FA style class and one icon class. Image URLs accept root-relative or HTTP(S) without credentials; scripts, data URLs and protocol-relative URLs are rejected, including for inactive icons. Images load in the browser with no referrer; no server-side image fetching is added.
- No new permissions, tokens, accounts, sessions, scheduled tasks or network/system changes. Custom image URLs can intentionally reference external hosts.
- Conventional dark data-theme values and .dark are supported; custom themes may override CSS variables.

## Display rule upgrades

Critical rules retain separate outcome-count and percentage values; switching modes changes presentation only. Percentage bands floor the inclusive face count times the percentage, include at least one, and cap each end at half the faces. Saved settings without a mode infer percentage only when they contain criticalPercent and no criticalCount; otherwise they infer count. Explicit modes always win.

Legacy digitsMin is expanded to repeatDigitsMin and sequenceDigitsMin in memory; explicit split fields win. New defaults are 2 repeated digits and 3 sequential digits. Saving the settings form writes normalized current keys, while simply loading the extension does not rewrite the database. Chinese fallback names change to 连号 (repeated) and 同花顺 (sequential); internal keys, configured labels, colors and result ledgers remain unchanged.

Pointer presses on a static result prevent focus acquisition, and clear any prior keyboard focus, so Flarum's focus-triggered tooltip cannot stick after a click. Tab navigation and hover remain available; no direct Bootstrap tooltip manipulation is added.
